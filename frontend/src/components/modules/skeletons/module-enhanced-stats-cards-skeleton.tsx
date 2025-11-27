import { Skeleton } from '@/components/ui/skeleton';

interface Props {
  count?: number;
}

export function EnhancedStatsCardsSkeleton({ count = 4 }: Readonly<Props>) {
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="border-border bg-card overflow-hidden rounded-lg border shadow-sm">
          <div className="flex items-center p-5">
            <div className="border-border bg-muted flex h-12 w-12 shrink-0 items-center justify-center rounded-full border">
              <Skeleton className="h-6 w-6 rounded" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <Skeleton className="h-4 w-24" />
              <Skeleton className="mt-2 h-7 w-20" />
              <Skeleton className="mt-2 h-4 w-40" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
