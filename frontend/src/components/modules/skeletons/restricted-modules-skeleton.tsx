import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

interface Props {
  count?: number;
  className?: string;
}

export function RestrictedModulesSkeleton({ count = 2, className }: Readonly<Props>) {
  return (
    <div className={`grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 ${className}`}>
      {Array.from({ length: count }).map((_, i) => (
        <Card key={i} className="opacity-70">
          <div className="flex flex-row items-center justify-between space-y-0 border-b p-4">
            <Skeleton className="h-5 w-2/5" />
            <Skeleton className="h-10 w-10 rounded-full" />
          </div>
          <div className="space-y-4 p-4">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-10 w-full rounded-md" />
          </div>
        </Card>
      ))}
    </div>
  );
}
