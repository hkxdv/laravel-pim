import { type ColumnDef, type Row } from '@tanstack/react-table';

/**
 * Función para filtrar datos de forma difusa, útil para tablas TanStack
 * @param row Fila a filtrar
 * @param columnId ID de la columna
 * @param value Valor de filtrado
 * @returns Booleano que indica si el valor coincide con el filtro
 */
export function fuzzyFilter<T>(
  row: Row<T>,
  columnId: string,
  value: string,
  addMeta: (meta: Record<string, unknown>) => void,
): boolean {
  // Función de búsqueda difusa
  const itemRank = rankItem(row.getValue(columnId), value);

  // Guardar la puntuación para usarla en ordenamiento/resaltado
  addMeta({ itemRank });

  // Devolver si la puntuación está por encima del umbral
  return itemRank.passed;
}

/**
 * Función para crear definiciones de columnas estandarizadas
 * @param columns Definiciones básicas de columnas
 * @returns Definiciones de columnas formateadas para TanStack Table
 */
export function createColumnDefs<T>({
  columns,
  enableSorting = true,
}: {
  columns: {
    header: string;
    accessorKey?: keyof T;
    accessorFn?: (row: T) => unknown;
    cell?: (props: { row: { original: T } }) => React.ReactNode;
    enableSorting?: boolean;
    meta?: Record<string, unknown>;
  }[];
  enableSorting?: boolean;
}): ColumnDef<T>[] {
  return columns.map((column) => ({
    header: column.header,
    ...(column.accessorKey ? { accessorKey: column.accessorKey as string } : {}),
    ...(column.accessorFn ? { accessorFn: column.accessorFn } : {}),
    ...(column.cell ? { cell: column.cell } : {}),
    enableSorting: column.enableSorting ?? enableSorting,
    ...(column.meta ? { meta: column.meta } : {}),
  }));
}

/**
 * Función para exportar datos de una tabla a CSV
 * @param data Datos a exportar
 * @param filename Nombre del archivo
 * @param columns Definiciones de columnas para encabezados
 */
export function exportToCsv<T>(
  data: T[],
  filename: string,
  columns: {
    header: string;
    accessorKey?: keyof T;
    accessorFn?: (row: T) => unknown;
  }[],
): void {
  // Crear encabezados
  const headers = columns.map((col) => col.header);

  // Crear filas de datos
  const rows = data.map((row) => {
    return columns.map((col) => {
      if (col.accessorKey) {
        return row[col.accessorKey];
      } else if (col.accessorFn) {
        return col.accessorFn(row);
      }
      return '';
    });
  });

  // Combinar en CSV
  const csvContent = [
    headers.join(','),
    ...rows.map((row) =>
      row
        .map((cell) =>
          typeof cell === 'string' ? `"${cell.replaceAll('"', '""')}"` : String(cell),
        )
        .join(','),
    ),
  ].join('\n');

  // Crear blob y descargar
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);

  link.setAttribute('href', url);
  link.setAttribute('download', `${filename}.csv`);
  link.style.visibility = 'hidden';

  document.body.append(link);
  link.click();
  link.remove();
}

// Funciones auxiliares (agregar rankItem si no está disponible)
function rankItem(item: unknown, query: string): { passed: boolean } {
  if (!query) return { passed: true };

  const itemStr = String(item).toLowerCase();
  const queryStr = query.toLowerCase();

  return {
    passed: itemStr.includes(queryStr),
  };
}
