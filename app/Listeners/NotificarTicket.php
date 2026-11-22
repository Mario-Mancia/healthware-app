<?php

namespace App\Listeners;

use App\Events\TicketCambiado;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Str;

/**
 * Genera las notificaciones de los tickets.
 *
 * Todo cambio de estado se avisa al emisor y al administrador que sigue el
 * reporte. Las notas avisan solo a la otra parte. Los administradores que no
 * atienden el reporte solo reciben el aviso de «nuevo» o «disponible de nuevo».
 */
class NotificarTicket
{
    /** @var array<int, array{0: string, 1: string}> Destinatarios de este evento: id => [título, mensaje]. */
    private array $avisos = [];

    public function handle(TicketCambiado $evento): void
    {
        $this->avisos = [];

        $ticket = $evento->ticket;
        $actor = $this->nombre(Usuario::query()->find($evento->idActor));
        $emisor = $ticket->usuario;
        $codigo = $ticket->codigo();
        $comentario = trim((string) $evento->comentario);

        switch ($evento->accion) {
            case Ticket::EVENTO_CREADO:
                $this->aviso((int) $ticket->id_usuario, 'Reporte registrado: ' . $codigo,
                    'Recibimos su reporte «' . $ticket->titulo . '». Un administrador lo tomará y le avisaremos de cada cambio.');

                $prefijo = in_array($ticket->prioridad, ['alta', 'urgente'], true)
                    ? 'Reporte ' . Str::lower($ticket->etiquetaPrioridad()) . ' '
                    : 'Nuevo reporte ';

                foreach (Ticket::idsAdministradores()->reject(fn (int $id) => $id === $evento->idActor) as $idAdmin) {
                    $this->aviso($idAdmin, $prefijo . $codigo . ': ' . $ticket->titulo,
                        $this->nombre($emisor) . ' reportó un problema (' . Str::lower(Str::before($ticket->etiquetaTipo(), ' (')) . ', prioridad '
                        . Str::lower($ticket->etiquetaPrioridad()) . '). ' . Str::limit($ticket->descripcion, 120));
                }
                break;

            case Ticket::EVENTO_TOMADO:
                $this->aviso((int) $ticket->id_usuario, 'Su reporte fue tomado: ' . $codigo,
                    $actor . ' tomó su reporte «' . $ticket->titulo . '» y lo está atendiendo.');
                $this->aviso($evento->idActor, 'Usted tomó el reporte ' . $codigo,
                    'Ahora es responsable de «' . $ticket->titulo . '», emitido por ' . $this->nombre($emisor) . '.');
                break;

            case Ticket::EVENTO_LIBERADO:
                $this->aviso((int) $ticket->id_usuario, 'Su reporte volvió a la cola: ' . $codigo,
                    $actor . ' liberó su reporte «' . $ticket->titulo . '». Otro administrador lo tomará.');
                $this->aviso($evento->idActor, 'Usted liberó el reporte ' . $codigo,
                    '«' . $ticket->titulo . '» volvió a la cola de reportes.');

                foreach (Ticket::idsAdministradores()->reject(fn (int $id) => $id === $evento->idActor || $id === (int) $ticket->id_usuario) as $idAdmin) {
                    $this->aviso($idAdmin, 'Reporte disponible: ' . $codigo,
                        '«' . $ticket->titulo . '» volvió a la cola y puede tomarse.');
                }
                break;

            case Ticket::EVENTO_COMPLETADO:
                $this->aviso((int) $ticket->id_usuario, 'Su reporte fue completado: ' . $codigo,
                    $actor . ' completó «' . $ticket->titulo . '».' . $this->detalle('Resolución', $comentario));
                $this->aviso($evento->idActor, 'Usted completó el reporte ' . $codigo,
                    'Cerró «' . $ticket->titulo . '» como completado.');
                break;

            case Ticket::EVENTO_DESCARTADO:
                $this->aviso((int) $ticket->id_usuario, 'Su reporte fue descartado: ' . $codigo,
                    $actor . ' descartó «' . $ticket->titulo . '».' . $this->detalle('Motivo', $comentario));
                $this->aviso($evento->idActor, 'Usted descartó el reporte ' . $codigo,
                    'Cerró «' . $ticket->titulo . '» como descartado.');
                break;

            case Ticket::EVENTO_CANCELADO:
                $this->aviso((int) $ticket->id_usuario, 'Reporte cancelado: ' . $codigo,
                    'Canceló su reporte «' . $ticket->titulo . '».' . $this->detalle('Motivo', $comentario));

                if ($ticket->id_admin) {
                    $this->aviso((int) $ticket->id_admin, 'Reporte cancelado por el emisor: ' . $codigo,
                        $this->nombre($emisor) . ' canceló «' . $ticket->titulo . '», que usted atendía.' . $this->detalle('Motivo', $comentario));
                }
                break;

            case Ticket::EVENTO_NOTA:
                $destinatario = $evento->idActor === (int) $ticket->id_usuario
                    ? (int) $ticket->id_admin
                    : (int) $ticket->id_usuario;

                if ($destinatario > 0) {
                    $this->aviso($destinatario, 'Nueva nota en ' . $codigo . ': ' . $ticket->titulo,
                        $actor . ': ' . Str::limit($comentario, 160));
                }
                break;
        }

        $this->enviar($ticket);
    }

    private function aviso(int $idUsuario, string $titulo, string $mensaje): void
    {
        // Si un mismo usuario corresponde a dos avisos, se queda con el primero.
        $this->avisos[$idUsuario] ??= [$titulo, $mensaje];
    }

    private function enviar(Ticket $ticket): void
    {
        // Ruta relativa: sirve en cualquier host y la acepta notificaciones.abrir
        $enlace = route('tickets.ver', ['id' => $ticket->id], false);

        foreach ($this->avisos as $idUsuario => [$titulo, $mensaje]) {
            Notificacion::notificar($idUsuario, 'ticket', $titulo, $mensaje, $enlace);
        }
    }

    private function detalle(string $etiqueta, string $texto): string
    {
        return $texto !== '' ? ' ' . $etiqueta . ': ' . Str::limit($texto, 200) : '';
    }

    private function nombre(?Usuario $usuario): string
    {
        return $usuario ? trim($usuario->nombres . ' ' . $usuario->apellidos) : 'Un usuario';
    }
}
