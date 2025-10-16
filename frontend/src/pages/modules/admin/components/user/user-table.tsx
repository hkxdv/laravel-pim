import { Pagination } from '@/components/data/data-pagination';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type { StaffUser } from '@/types';
import { type Table as TanstackTable, flexRender } from '@tanstack/react-table';
import { Info } from 'lucide-react';

interface UsersTableProps {
  table: TanstackTable<StaffUser>;
  totalItems?: number;
}

export default function UsersTable({ table, totalItems }: Readonly<UsersTableProps>) {
  // Asegurar que pageCount nunca sea negativo o cero
  const pageCount = Math.max(table.getPageCount(), 1);
  const currentPage = table.getState().pagination.pageIndex + 1;
  const hasData = table.getRowModel().rows.length > 0;
  const perPage = table.getState().pagination.pageSize;
  const perPageOptions = [10, 20, 50, 100];

  return (
    <div className="w-full">
      <div className="border-border w-full overflow-auto rounded-md border">
        <Table className="border-collapse">
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow
                key={headerGroup.id}
                className="border-border bg-muted/40 hover:bg-muted/40 border-b"
              >
                {headerGroup.headers.map((header, index) => (
                  <TableHead
                    key={header.id}
                    className={`border-r ${index === headerGroup.headers.length - 1 ? 'border-r-0' : ''} whitespace-nowrap`}
                  >
                    {header.isPlaceholder
                      ? null
                      : flexRender(header.column.columnDef.header, header.getContext())}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {hasData ? (
              table.getRowModel().rows.map((row, rowIndex) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className={`${
                    rowIndex % 2 === 0 ? 'bg-background' : 'bg-muted/40'
                  } hover:bg-muted/50 ${
                    rowIndex < table.getRowModel().rows.length - 1 ? 'border-border border-b' : ''
                  }`}
                >
                  {row.getVisibleCells().map((cell, index) => (
                    <TableCell
                      key={cell.id}
                      className={`border-r ${index === row.getVisibleCells().length - 1 ? 'border-r-0' : ''}`}
                    >
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={table.getAllColumns().length} className="h-24 text-center">
                  <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="bg-muted rounded-full p-3">
                      <Info className="text-muted-foreground h-6 w-6" strokeWidth={1.5} />
                    </div>
                    <h3 className="mt-4 text-lg font-medium">Sin usuarios</h3>
                    <p className="text-muted-foreground mt-2 max-w-xs text-sm">
                      No se encontraron usuarios para mostrar.
                    </p>
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <div className="bg-card text-card-foreground border-border rounded-b-md border-x border-b px-4 py-2">
        <Pagination
          currentPage={currentPage}
          totalPages={pageCount}
          onPageChange={(page) => {
            const target = Math.max(1, Math.min(page, pageCount));
            if (target !== currentPage) {
              table.setPageIndex(target - 1);
            }
          }}
          totalItems={
            typeof totalItems === 'number' ? totalItems : table.getFilteredRowModel().rows.length
          }
          perPageOptions={perPageOptions}
          perPage={perPage}
          onPerPageChange={(newSize) => {
            if (typeof newSize === 'number' && newSize > 0 && newSize !== perPage) {
              table.setPageSize(newSize);
              // Opcional: resetear a la primera página para evitar quedar fuera de rango
              table.setPageIndex(0);
            }
          }}
        />
      </div>
    </div>
  );
}
