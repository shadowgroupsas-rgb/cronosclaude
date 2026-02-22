// Utilidad para verificar si un punto está dentro de un polígono (geocerca)
// Algoritmo: Ray casting (point-in-polygon)

export interface GeoPoint {
  lat: number;
  lng: number;
}

/**
 * Determina si un punto está dentro de un polígono usando el algoritmo Ray Casting.
 * @param point Punto a verificar {lat, lng}
 * @param polygon Array de puntos que forman el polígono [{lat, lng}, ...]
 * @returns true si el punto está dentro del polígono
 */
export function isPointInPolygon(point: GeoPoint, polygon: GeoPoint[]): boolean {
  if (!polygon || polygon.length < 3) {
    return false;
  }

  let inside = false;
  const x = point.lat;
  const y = point.lng;

  for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
    const xi = polygon[i].lat;
    const yi = polygon[i].lng;
    const xj = polygon[j].lat;
    const yj = polygon[j].lng;

    const intersect =
      yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi;

    if (intersect) {
      inside = !inside;
    }
  }

  return inside;
}

/**
 * Calcula la distancia entre dos puntos geográficos en metros (fórmula Haversine).
 */
export function distanceBetweenPoints(p1: GeoPoint, p2: GeoPoint): number {
  const R = 6371000; // Radio de la tierra en metros
  const dLat = toRad(p2.lat - p1.lat);
  const dLng = toRad(p2.lng - p1.lng);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(p1.lat)) * Math.cos(toRad(p2.lat)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

function toRad(degrees: number): number {
  return degrees * (Math.PI / 180);
}
