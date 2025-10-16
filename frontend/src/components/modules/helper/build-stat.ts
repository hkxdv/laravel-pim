import type { EnhancedStat } from '../module-enhanced-stats-cards';

/**
 * Crea una estadística estandarizada para las tarjetas de dashboard de módulos.
 */
export const buildStat = (
  title: string,
  value: number | undefined,
  description: string,
  icon: string,
): EnhancedStat => ({
  title,
  value: value ?? 0,
  description,
  icon,
});
