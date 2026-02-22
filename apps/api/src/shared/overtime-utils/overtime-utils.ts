// Utilidades para cálculos de horas extras

/**
 * Determina si un registro de hora extra es nocturno.
 * Hora extra nocturna: cualquier porción cae entre las 19:00 y las 06:00 del día siguiente.
 */
export function isNocturnalOvertime(clockIn: Date, clockOut: Date): boolean {
  const clockInHour = clockIn.getHours();
  const clockOutHour = clockOut.getHours();

  // Si el clock-in es después de las 19:00 o antes de las 06:00
  if (clockInHour >= 19 || clockInHour < 6) {
    return true;
  }

  // Si el clock-out es después de las 19:00 o antes de las 06:00
  if (clockOutHour >= 19 || clockOutHour < 6) {
    return true;
  }

  // Si el registro cruza la medianoche (clock-out es de un día diferente)
  const clockInDate = new Date(clockIn.getFullYear(), clockIn.getMonth(), clockIn.getDate());
  const clockOutDate = new Date(clockOut.getFullYear(), clockOut.getMonth(), clockOut.getDate());
  if (clockOutDate.getTime() > clockInDate.getTime()) {
    return true;
  }

  // Si el período abarca las 19:00
  if (clockInHour < 19 && clockOutHour >= 19) {
    return true;
  }

  return false;
}

/**
 * Calcula los minutos totales entre clock-in y clock-out.
 */
export function calculateTotalMinutes(clockIn: Date, clockOut: Date): number {
  const diffMs = clockOut.getTime() - clockIn.getTime();
  return Math.round(diffMs / 60000);
}

/**
 * Obtiene el día de la semana basado en la hora de clock-in.
 * lunes=1, martes=2, ..., domingo=7 (formato ISO)
 */
export function getDayOfWeek(date: Date): number {
  const day = date.getDay();
  // getDay() devuelve 0=domingo, 1=lunes... Convertir a ISO: 1=lunes, 7=domingo
  return day === 0 ? 7 : day;
}

/**
 * Formatea duración en minutos a texto legible (ej: "2h 30min")
 */
export function formatDuration(totalMinutes: number): string {
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  if (hours === 0) {
    return `${minutes}min`;
  }
  if (minutes === 0) {
    return `${hours}h`;
  }
  return `${hours}h ${minutes}min`;
}
