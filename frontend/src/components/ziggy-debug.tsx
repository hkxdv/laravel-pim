import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from './ui/button';
import { ScrollArea } from './ui/scroll-area';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from './ui/sheet';

export function ZiggyDebug() {
  const [isOpen, setIsOpen] = useState(false);
  const { ziggy } = usePage().props;

  const routeCount = Object.keys(ziggy.routes).length;

  return (
    <Sheet open={isOpen} onOpenChange={setIsOpen}>
      <SheetTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className="fixed right-4 bottom-4 z-50 bg-orange-500 text-white hover:bg-orange-600"
        >
          Routes: {routeCount}
        </Button>
      </SheetTrigger>
      <SheetContent className="w-[400px] overflow-auto sm:w-[540px]">
        <SheetHeader>
          <SheetTitle>Ziggy Routes ({routeCount})</SheetTitle>
        </SheetHeader>
        <ScrollArea className="mt-4 h-[80vh]">
          <div className="space-y-4">
            <div>
              <h3 className="mb-2 font-medium">Current URL:</h3>
              <pre className="bg-muted rounded p-2 text-xs">{ziggy.location}</pre>
            </div>

            <div>
              <h3 className="mb-2 font-medium">Available Routes:</h3>
              <pre className="bg-muted rounded p-2 text-xs whitespace-pre-wrap">
                {JSON.stringify(
                  Object.keys(ziggy.routes).toSorted((a, b) => a.localeCompare(b)),
                  null,
                  2,
                )}
              </pre>
            </div>
          </div>
        </ScrollArea>
      </SheetContent>
    </Sheet>
  );
}
