<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Appointment extends Model
{
    protected $table = 'citas';
    protected $fillable = [
            'paciente_id', 'doctor_id', 'sede_id', 'fecha', 
            'hora_inicio', 'hora_fin', 'razon', 'estado', 'pago',
        'diagnostico_id', 'observaciones_medicas', 'receta', 'calendario_id'
        ];
    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i:s',
        'hora_fin' => 'datetime:H:i:s'
    ];
    
    public $timestamps = false;
    
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
    
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    public function pagos()
    {
        return $this->hasMany(Payment::class, 'cita_id');
    }
    
    
    public static function updateStatus($id, $status): bool
    {
        $allowed = ['pendiente','confirmado','atendido','cancelado'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $appointment = static::find($id);
        if (!$appointment) {
            return false;
        }

        $oldStatus = $appointment->estado;
        $appointment->estado = $status;
        $result = $appointment->save();

        // Si se cancela la cita, liberar el slot en slots_calendario
        if ($result && $status === 'cancelado' && $oldStatus !== 'cancelado') {
            DB::table('slots_calendario')
                ->where('reservado_por_cita_id', $id)
                ->update([
                    'reservado_por_cita_id' => null,
                    'disponible' => 1
                ]);
        }

        return $result;
    }
    
    public static function updatePayment($id, $paymentStatus)
    {
        $allowed = ['pendiente','pagado','rechazado'];
        if (!in_array($paymentStatus, $allowed, true)) {
            return false;
        }
        
        $db = \App\Core\SimpleDatabase::getInstance();
        
        // Verificar que la cita existe y está en estado 'atendido'
        $cita = $db->fetchOne("SELECT estado FROM citas WHERE id = ?", [$id]);
        if (!$cita || $cita['estado'] !== 'atendido') {
            return false;
        }
        
        return $db->update('citas', ['pago' => $paymentStatus], 'id = ?', [$id]);
    }
    
    public static function overlapsWindow($date, $startTime, $endTime, $doctorId, $sedeId = null)
    {
        $db = \App\Core\SimpleDatabase::getInstance();

        $sql = "SELECT COUNT(*) as count FROM citas
                WHERE fecha = ?
                AND estado != 'cancelado'
                AND (hora_inicio < ? AND hora_fin > ?)
                AND (
                    doctor_id = ?
                    OR sede_id = ?
                )";

        $params = [$date, $endTime, $startTime, $doctorId, $sedeId, $sedeId];

        $result = $db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    public static function upcomingForUser($userId, $limit = 5)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        // Detectar el tipo de base de datos y usar la sintaxis correcta
        $dbType = $db->getConnectionType();
        
        if ($dbType === 'sqlsrv') {
            // SQL Server usa TOP - el valor debe ser literal, no parámetro
            $sql = "SELECT TOP " . (int)$limit . " c.*, 
                           pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                           du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                           e.nombre as especialidad_nombre,
                           s.nombre_sede
                    FROM citas c
                    LEFT JOIN pacientes p ON c.paciente_id = p.id
                    LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                    LEFT JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN usuarios du ON d.usuario_id = du.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    LEFT JOIN sedes s ON c.sede_id = s.id
                    WHERE pu.id = ? 
                    AND c.fecha >= ? 
                    AND c.estado != 'cancelado'
                    ORDER BY c.fecha ASC, c.hora_inicio ASC";
            
            return $db->fetchAll($sql, [$userId, date('Y-m-d')]);
        } else {
            // SQLite/MySQL usan LIMIT
            $sql = "SELECT c.*, 
                           pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                           du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                           e.nombre as especialidad_nombre,
                           s.nombre_sede
                    FROM citas c
                    LEFT JOIN pacientes p ON c.paciente_id = p.id
                    LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                    LEFT JOIN doctores d ON c.doctor_id = d.id
                    LEFT JOIN usuarios du ON d.usuario_id = du.id
                    LEFT JOIN especialidades e ON d.especialidad_id = e.id
                    LEFT JOIN sedes s ON c.sede_id = s.id
                    WHERE pu.id = ? 
                    AND c.fecha >= ? 
                    AND c.estado != 'cancelado'
                    ORDER BY c.fecha ASC, c.hora_inicio ASC
                    LIMIT ?";
            
            return $db->fetchAll($sql, [$userId, date('Y-m-d'), $limit]);
        }
    }
    
    public static function usercitas($userId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE pu.id = ?
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql, [$userId]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function doctorcitas($doctorId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.doctor_id = ?
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql, [$doctorId]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function doctorCitasToday($doctorId, $date)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                WHERE c.doctor_id = ? AND c.fecha = ?
                ORDER BY c.hora_inicio ASC";
        
        $appointments = $db->fetchAll($sql, [$doctorId, $date]);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function listAll()
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $sql = "SELECT c.*, 
                       pu.nombre as paciente_nombre, pu.apellido as paciente_apellido,
                       du.nombre as doctor_nombre, du.apellido as doctor_apellido,
                       e.nombre as especialidad_nombre,
                       s.nombre_sede
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                LEFT JOIN doctores d ON c.doctor_id = d.id
                LEFT JOIN usuarios du ON d.usuario_id = du.id
                LEFT JOIN especialidades e ON d.especialidad_id = e.id
                LEFT JOIN sedes s ON c.sede_id = s.id
                ORDER BY c.fecha DESC, c.hora_inicio DESC";
        
        $appointments = $db->fetchAll($sql);
        
        // Convertir a formato plano para la vista
        return array_map(function($appointment) {
            return [
                'id' => $appointment['id'],
                'fecha' => $appointment['fecha'],
                'hora_inicio' => $appointment['hora_inicio'],
                'hora_fin' => $appointment['hora_fin'],
                'razon' => $appointment['razon'],
                'estado' => $appointment['estado'],
                'pago' => $appointment['pago'],
                'paciente_nombre' => $appointment['paciente_nombre'] ?? '',
                'paciente_apellido' => $appointment['paciente_apellido'] ?? '',
                'doctor_nombre' => $appointment['doctor_nombre'] ?? '',
                'doctor_apellido' => $appointment['doctor_apellido'] ?? '',
                'especialidad_nombre' => $appointment['especialidad_nombre'] ?? 'N/A',
                'sede_nombre' => $appointment['nombre_sede'] ?? 'N/A'
            ];
        }, $appointments);
    }
    
    public static function createAppointment($pacienteId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, $razon)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        
        $data = [
            'paciente_id' => $pacienteId,
            'doctor_id' => $doctorId,
            'sede_id' => $sedeId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'razon' => $razon,
            'estado' => 'pendiente',
            'pago' => 'pendiente'
        ];
        
        $citaId = $db->insert('citas', $data);
        
        return $citaId;
    }
    
    public static function belongsToDoctor($citaId, $doctorId)
    {
        $db = \App\Core\SimpleDatabase::getInstance();
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM citas WHERE id = ? AND doctor_id = ?", [$citaId, $doctorId]);
        return $result['count'] > 0;
    }
    
    public static function cancelByPatient($citaId, $userId)
    {
        $cita = static::with('paciente.usuario')
                     ->where('id', $citaId)
                     ->whereHas('paciente.usuario', function($query) use ($userId) {
                         $query->where('id', $userId);
                     })
                     ->first();
        
        if (!$cita) {
            return false;
        }
        
        // Verificar si se puede cancelar (24 horas antes)
        // Normalizar fecha y hora por separado (puede venir como DateTime/Carbon u otros formatos)
        try {
            // Fecha (YYYY-MM-DD)
            if (is_object($cita->fecha) && method_exists($cita->fecha, 'format')) {
                $fechaPart = $cita->fecha->format('Y-m-d');
            } else {
                $fechaPart = date('Y-m-d', strtotime((string)$cita->fecha));
            }
        } catch (\Throwable $e) {
            $fechaPart = date('Y-m-d', strtotime((string)$cita->fecha));
        }

        // Hora (HH:MM:SS)
        try {
            if (is_object($cita->hora_inicio) && method_exists($cita->hora_inicio, 'format')) {
                $horaPart = $cita->hora_inicio->format('H:i:s');
            } else {
                $horaStr = (string)$cita->hora_inicio;
                $t = strtotime($horaStr);
                if ($t !== false) {
                    // Si trae fecha+hora, obtenemos solo la hora
                    $horaPart = date('H:i:s', $t);
                } else {
                    if (preg_match('/(\d{1,2}:\d{2}(?::\d{2})?)/', $horaStr, $m)) {
                        $horaPart = strlen($m[1]) === 5 ? $m[1] . ':00' : $m[1];
                    } else {
                        $horaPart = '00:00:00';
                    }
                }
            }
        } catch (\Throwable $e) {
            $horaPart = date('H:i:s', strtotime((string)$cita->hora_inicio));
        }

        $fechaCita = trim($fechaPart . ' ' . $horaPart);
        $fechaLimite = date('Y-m-d H:i:s', strtotime($fechaCita . ' -24 hours'));

        if (date('Y-m-d H:i:s') > $fechaLimite) {
            return false; // No se puede cancelar
        }
        
        $result = $cita->update(['estado' => 'cancelado']);

        // Si se canceló exitosamente, liberar el slot en slots_calendario
        if ($result) {
            DB::table('slots_calendario')
                ->where('reservado_por_cita_id', $citaId)
                ->update([
                    'reservado_por_cita_id' => null,
                    'disponible' => 1
                ]);
        }

        return $result;
    }
    
    public static function canModify($citaId, $userId)
    {
        $cita = static::with('paciente.usuario')
                     ->where('id', $citaId)
                     ->whereHas('paciente.usuario', function($query) use ($userId) {
                         $query->where('id', $userId);
                     })
                     ->first();
        
        if (!$cita) {
            return false;
        }
        
        // Solo se puede modificar si está pendiente o confirmado
        return in_array($cita->estado, ['pendiente', 'confirmado']);
    }
    
    /**
     * Modifica una cita y maneja la reserva de slots en slots_calendario.
     * - Libera el slot anterior (si estaba reservado por esta cita)
     * - Reserva el nuevo slot indicado por $calendarioId (si se proporciona)
     * Todo dentro de una transacción para mantener consistencia.     
     */
    public static function modifyAppointment($citaId, $doctorId, $sedeId, $fecha, $horaInicio, $horaFin, $razon, $calendarioId = null, $slotId = null)
    {
        // Calcular hora_fin si no viene explícita
        $horaFinCalculada = $horaFin ?: date('H:i:s', strtotime(($horaInicio ?? '00:00:00') . ' +15 minutes'));

        DB::beginTransaction();
        try {
            // PASO 1: Obtener la cita y su row_version para verificación optimista
            $appointmentRow = DB::selectOne(
                "SELECT *, row_version FROM citas WHERE id = ?",
                [$citaId]
            );
            
            if (!$appointmentRow) {
                DB::rollBack();
                return false;
            }
            
            $citaRowVersion = $appointmentRow->row_version;
            $oldCalendario = $appointmentRow->calendario_id ?? null;

            // PASO 2: PRIMERO actualizar la cita con bloqueo optimista
            // Esto asegura que solo UNA sesión puede proceder
            $citaRowVersionHex = '0x' . bin2hex($citaRowVersion);
            
            $affectedCita = DB::update(
                "UPDATE citas 
                 SET doctor_id = ?, sede_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, razon = ?, calendario_id = ?
                 WHERE id = ? AND row_version = " . $citaRowVersionHex,
                [
                    $doctorId,
                    $sedeId,
                    $fecha,
                    $horaInicio,
                    $horaFinCalculada,
                    $razon,
                    $calendarioId ?? $oldCalendario,
                    $citaId
                ]
            );
            
            error_log("Cita update with optimistic lock: affected=$affectedCita for citaId=$citaId");
            
            if ($affectedCita <= 0) {
                // El row_version de la cita cambió - otra sesión la modificó primero
                error_log("Conflict: cita $citaId was modified by another session");
                DB::rollBack();
                return false;
            }

            // PASO 3: Ahora que tenemos la "propiedad" de la cita, manejar los slots
            // Obtener slot anterior (si existe) reservado por esta cita
            $oldSlot = DB::selectOne(
                "SELECT id, calendario_id, disponible, reservado_por_cita_id, row_version
                 FROM slots_calendario 
                 WHERE reservado_por_cita_id = ?",
                [$citaId]
            );
            $oldSlotId = $oldSlot->id ?? null;

            error_log("modifyAppointment: citaId=$citaId, oldCalendario=$oldCalendario, oldSlotId=$oldSlotId, newCalendario=$calendarioId, newSlotId=$slotId");

            // Si se proporcionó slotId, intentamos reservar el nuevo slot
            if ($slotId) {
                // Obtener el slot destino con su row_version
                $targetSlot = DB::selectOne(
                    "SELECT id, calendario_id, disponible, reservado_por_cita_id, row_version
                     FROM slots_calendario 
                     WHERE id = ? 
                       AND (reservado_por_cita_id IS NULL OR reservado_por_cita_id = ?)",
                    [$slotId, $citaId]
                );
                
                if (!$targetSlot) {
                    // El slot no está disponible - rollback
                    DB::rollBack();
                    return false;
                }
                
                $rowVersion = $targetSlot->row_version;
                $rowVersionHex = '0x' . bin2hex($rowVersion);
                
                // Reservar el nuevo slot con bloqueo optimista
                $affected = DB::update(
                    "UPDATE slots_calendario 
                     SET reservado_por_cita_id = ?, disponible = 0 
                     WHERE id = ? AND row_version = " . $rowVersionHex,
                    [$citaId, $slotId]
                );

                error_log("Reserved by slotId with optimistic lock: $affected rows for slotId=$slotId");

                if ($affected <= 0) {
                    // El slot fue tomado por otra persona - rollback
                    DB::rollBack();
                    return false;
                }

                // Liberar slot anterior si era distinto
                if ($oldSlotId && $oldSlotId != $slotId) {
                    DB::table('slots_calendario')
                        ->where('id', $oldSlotId)
                        ->where('reservado_por_cita_id', $citaId)
                        ->update([
                            'reservado_por_cita_id' => null,
                            'disponible' => 1
                        ]);
                }
            } else if ($calendarioId && $calendarioId != $oldCalendario) {
                // No se proporcionó slotId pero sí calendarioId diferente
                $targetSlot = DB::selectOne(
                    "SELECT id, calendario_id, disponible, reservado_por_cita_id, row_version
                     FROM slots_calendario 
                     WHERE calendario_id = ? 
                       AND (reservado_por_cita_id IS NULL OR reservado_por_cita_id = ?)",
                    [$calendarioId, $citaId]
                );
                
                if (!$targetSlot) {
                    DB::rollBack();
                    return false;
                }
                
                $targetSlotId = $targetSlot->id;
                $rowVersion = $targetSlot->row_version;
                $rowVersionHex = '0x' . bin2hex($rowVersion);
                
                $affected = DB::update(
                    "UPDATE slots_calendario 
                     SET reservado_por_cita_id = ?, disponible = 0 
                     WHERE id = ? AND row_version = " . $rowVersionHex,
                    [$citaId, $targetSlotId]
                );

                if ($affected <= 0) {
                    DB::rollBack();
                    return false;
                }

                // Liberar antiguo slot si existía
                if ($oldSlotId) {
                    DB::table('slots_calendario')
                        ->where('id', $oldSlotId)
                        ->where('reservado_por_cita_id', $citaId)
                        ->update([
                            'reservado_por_cita_id' => null,
                            'disponible' => 1
                        ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            error_log("Exception in modifyAppointment: " . $e->getMessage() . " | " . $e->getTraceAsString());
            DB::rollBack();
            return false;
        }
    }

    public static function create(
        int $pacienteId,
        int $doctorId,
        ?int $sedeId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?string $razon = ''
    , ?int $calendarioId = null, ?string $slotHora = null
    ): int {
        // Crear cita y, si se proporciona calendario_id + slotHora, marcar el slot como reservado
        // Se utiliza bloqueo optimista con row_version para evitar reservas concurrentes
        DB::beginTransaction();
        try {
            // Si recibimos calendario_id y slotHora, primero verificamos y reservamos el slot
            // usando bloqueo optimista antes de crear la cita
            $slotId = null;
            $rowVersion = null;
            
            if ($calendarioId && $slotHora) {
                // Obtener el slot candidato disponible con su row_version
                // En SQL Server, row_version es de tipo timestamp (binario de 8 bytes)
                // Lo obtenemos para implementar bloqueo optimista
                $slot = DB::selectOne(
                    "SELECT id, calendario_id, hora_inicio, hora_fin, disponible, 
                            reservado_por_cita_id, row_version
                     FROM slots_calendario 
                     WHERE calendario_id = ? 
                       AND hora_inicio LIKE ? 
                       AND reservado_por_cita_id IS NULL",
                    [$calendarioId, $slotHora . '%']
                );
                
                if (!$slot) {
                    // El slot ya no está disponible
                    DB::rollBack();
                    throw new \Exception('El slot ya fue reservado por otro paciente');
                }
                
                $slotId = $slot->id;
                // Guardar row_version para verificación posterior
                $rowVersion = $slot->row_version;
            }

            // Crear la cita
            $appointment = new static();
            $appointment->paciente_id = $pacienteId;
            $appointment->doctor_id = $doctorId;
            $appointment->sede_id = $sedeId;
            $appointment->fecha = $fecha;
            $appointment->hora_inicio = $horaInicio;
            $appointment->hora_fin = $horaFin;
            $appointment->razon = $razon;
            $appointment->estado = 'pendiente';
            // si se proporciona calendario_id, guardarlo en la cita
            if ($calendarioId) $appointment->calendario_id = $calendarioId;
            $appointment->save();

            $createdId = $appointment->id;

            // Si teníamos un slot candidato, intentamos reservarlo con bloqueo optimista
            if ($slotId && $rowVersion !== null) {
                // Convertir row_version a formato hexadecimal para usarlo en SQL
                // Esto evita problemas de conversión de tipos binarios en los parámetros
                $rowVersionHex = '0x' . bin2hex($rowVersion);
                
                // Actualizar el slot verificando que row_version no haya cambiado (bloqueo optimista)
                // Usamos el valor hexadecimal directamente en la consulta SQL
                $affected = DB::update(
                    "UPDATE slots_calendario 
                     SET reservado_por_cita_id = ?, disponible = 0 
                     WHERE id = ? AND row_version = " . $rowVersionHex,
                    [$createdId, $slotId]
                );

                if ($affected <= 0) {
                    // El row_version cambió - otro proceso reservó el slot (conflicto de concurrencia)
                    DB::rollBack();
                    throw new \Exception('El slot ya fue reservado por otro paciente (conflicto de concurrencia)');
                }
            }

            DB::commit();
            return $createdId;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function belongsToPatient(int $id, int $userId): bool
    {
        return static::whereHas('paciente', function($query) use ($userId) {
            $query->where('usuario_id', $userId);
        })->where('id', $id)->exists();
    }

    public function getPacienteNombreAttribute(): ?string
    {
        return $this->paciente?->user?->nombre;
    }
    
    public function getPacienteApellidoAttribute(): ?string
    {
        return $this->paciente?->user?->apellido;
    }
    
    public function getDoctorNombreAttribute(): ?string
    {
        return $this->doctor?->user?->nombre;
    }
    
    public function getDoctorApellidoAttribute(): ?string
    {
        return $this->doctor?->user?->apellido;
    }
    
    public function getSedeNombreAttribute(): ?string
    {
        return $this->sede?->nombre_sede;
    }
    
    public function getEspecialidadNombreAttribute(): ?string
    {
        return $this->doctor?->especialidad?->nombre;
    }
}
