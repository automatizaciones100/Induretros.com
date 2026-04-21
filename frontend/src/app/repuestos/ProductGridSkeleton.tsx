/**
 * Skeleton que se muestra mientras ProductGrid hace streaming.
 * El usuario ve contenido inmediatamente en lugar de una pantalla vacía.
 */
export default function ProductGridSkeleton() {
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
      {Array.from({ length: 12 }).map((_, i) => (
        <div key={i} className="bg-white rounded-xl overflow-hidden border border-gray-100 animate-pulse">
          <div className="aspect-square bg-gray-100" />
          <div className="p-3 space-y-2">
            <div className="h-3 bg-gray-100 rounded w-1/2" />
            <div className="h-4 bg-gray-100 rounded w-full" />
            <div className="h-4 bg-gray-100 rounded w-3/4" />
            <div className="h-5 bg-gray-100 rounded w-1/3 mt-2" />
          </div>
        </div>
      ))}
    </div>
  );
}
