import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import React from 'react';
import { route } from 'ziggy-js';
import type { OrderDetailPageProps } from '../interfaces';

/**
 * Página Inertia de detalle de orden del Módulo de Ventas.
 *
 * Muestra información de la orden y provee acciones de cambio de estado
 * (marcar como preparado, cancelar) usando endpoints de la API.
 */
const OrderDetailPage: React.FC = () => {
  const { order, auth, breadcrumbs, pageTitle, description } = usePage<
    PageProps & OrderDetailPageProps
  >().props as OrderDetailPageProps & { auth: { user: unknown } };

  const user = extractUserData(auth.user as unknown as User);

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.sales.orders.detail', 'Detalle de orden');

  const o = order;
  const { showSuccess, showError, showFieldError } = useToastNotifications();
  const [pendingAction, setPendingAction] = React.useState<'none' | 'prepare' | 'cancel' | 'save'>(
    'none',
  );
  const [formStatus, setFormStatus] = React.useState<string>(o.status);
  const [formNotes, setFormNotes] = React.useState<string>('');
  const [actionError, setActionError] = React.useState<string>('');
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string[]>>({});

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title={pageTitle ?? `Orden #${o.id}`} />
      <ModuleDashboardLayout
        title={pageTitle ?? `Orden #${o.id}`}
        description={description ?? 'Detalle de la orden'}
        userName={getUserName(user)}
        actions={
          <div className="flex items-center gap-3">
            <Link href={route('internal.sales.orders.list')} className="text-sm">
              Volver al listado
            </Link>
            {o.status !== 'delivered' && (
              <button
                className="text-sm underline"
                disabled={pendingAction !== 'none'}
                onClick={() => {
                  setActionError('');
                  setPendingAction('prepare');
                  void axios
                    .patch(`/api/v1/sales/orders/${o.id}`, { status: 'prepared' })
                    .then(() => {
                      showSuccess('Orden marcada como preparada');
                      globalThis.location.reload();
                    })
                    .catch((error: unknown) => {
                      const axiosError = error as {
                        response?: { data?: { errors?: Record<string, string[] | string> } };
                      };
                      const messages = axiosError.response?.data?.errors ?? {};
                      for (const key in messages) {
                        const raw = messages[key];
                        const list = Array.isArray(raw)
                          ? raw.map(String)
                          : [typeof raw === 'string' ? raw : ''];
                        showFieldError(key, list.join(', '));
                        setFieldErrors((prev) => ({ ...prev, [key]: list }));
                      }
                      setActionError('No se pudo marcar como preparado');
                      showError('No se pudo marcar como preparado');
                    })
                    .finally(() => {
                      setPendingAction('none');
                    });
                }}
              >
                Marcar como preparado
              </button>
            )}
            {o.status !== 'delivered' && (
              <button
                className="text-sm text-red-600 underline"
                disabled={pendingAction !== 'none'}
                onClick={() => {
                  setActionError('');
                  setPendingAction('cancel');
                  void axios
                    .post(`/api/v1/sales/orders/${o.id}/cancel`)
                    .then(() => {
                      showSuccess('Orden cancelada');
                      globalThis.location.reload();
                    })
                    .catch(() => {
                      setActionError('No se pudo cancelar la orden');
                      showError('No se pudo cancelar la orden');
                    })
                    .finally(() => {
                      setPendingAction('none');
                    });
                }}
              >
                Cancelar orden
              </button>
            )}
            {actionError && <span className="text-xs text-red-600">{actionError}</span>}
          </div>
        }
        mainContent={
          <div className="space-y-4">
            {o.status !== 'delivered' && (
              <div className="space-y-3 rounded border p-3">
                <div className="font-medium">Editar orden</div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1">
                    <label htmlFor="order-status" className="text-muted-foreground text-sm">
                      Estado
                    </label>
                    <select
                      id="order-status"
                      className="rounded border px-2 py-1 text-sm"
                      value={formStatus}
                      onChange={(e) => {
                        setFormStatus(e.target.value);
                      }}
                    >
                      <option value="draft">Borrador</option>
                      <option value="requested">Solicitado</option>
                      <option value="prepared">Preparado</option>
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label htmlFor="order-notes" className="text-muted-foreground text-sm">
                      Notas
                    </label>
                    <textarea
                      id="order-notes"
                      className="rounded border px-2 py-1 text-sm"
                      rows={3}
                      value={formNotes}
                      onChange={(e) => {
                        setFormNotes(e.target.value);
                      }}
                      placeholder="Observaciones internas"
                    />
                  </div>
                </div>
                <div>
                  <button
                    className="text-sm underline"
                    disabled={pendingAction !== 'none'}
                    onClick={() => {
                      setActionError('');
                      setPendingAction('save');
                      void axios
                        .patch(`/api/v1/sales/orders/${o.id}`, {
                          status: formStatus,
                          notes: formNotes,
                        })
                        .then(() => {
                          showSuccess('Orden actualizada');
                          globalThis.location.reload();
                        })
                        .catch((error: unknown) => {
                          const axiosError = error as {
                            response?: { data?: { errors?: Record<string, string[] | string> } };
                          };
                          const messages = axiosError.response?.data?.errors ?? {};
                          for (const key in messages) {
                            const raw = messages[key];
                            const list = Array.isArray(raw)
                              ? raw.map(String)
                              : [typeof raw === 'string' ? raw : ''];
                            showFieldError(key, list.join(', '));
                            setFieldErrors((prev) => ({ ...prev, [key]: list }));
                          }
                          setActionError('No se pudo actualizar la orden');
                          showError('No se pudo actualizar la orden');
                        })
                        .finally(() => {
                          setPendingAction('none');
                        });
                    }}
                  >
                    Guardar cambios
                  </button>
                </div>
              </div>
            )}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <div className="text-muted-foreground text-sm">Estado</div>
                <div className="text-base font-medium">{o.status}</div>
              </div>
              <div>
                <div className="text-muted-foreground text-sm">Total</div>
                <div className="text-base font-medium">{o.total}</div>
              </div>
              <div>
                <div className="text-muted-foreground text-sm">Creada</div>
                <div className="text-base">{o.created_at ?? ''}</div>
              </div>
              <div>
                <div className="text-muted-foreground text-sm">Entregada</div>
                <div className="text-base">{o.delivered_at ?? ''}</div>
              </div>
            </div>
            <div>
              <div className="font-medium">Ítems</div>
              <div className="mt-2 space-y-2">
                {(o.items ?? []).map((it) => (
                  <div key={it.id} className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">
                        {it.product?.name ?? `Producto #${it.product_id}`}
                      </div>
                      <div className="text-muted-foreground text-xs">
                        SKU: {it.product?.sku ?? ''}
                      </div>
                      <div className="mt-1 flex items-center gap-2">
                        {(() => {
                          const stock = it.product?.stock ?? 0;
                          const qty = it.qty;
                          const ok = stock >= qty;
                          const label = ok ? 'Disponible' : 'Insuficiente';
                          const variantStr: 'success' | 'destructive' = ok
                            ? 'success'
                            : 'destructive';
                          return (
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Badge variant={variantStr}>{label}</Badge>
                              </TooltipTrigger>
                              <TooltipContent>
                                Stock: {stock} • Requerido: {qty}
                              </TooltipContent>
                            </Tooltip>
                          );
                        })()}
                        {(() => {
                          const stock = it.product?.stock ?? 0;
                          const qty = it.qty;
                          const ok = stock >= qty;
                          const sku = it.product?.sku ?? '';
                          if (ok) return null;
                          return (
                            <Link
                              href={route('internal.inventory.stock_movements.create', {
                                sku,
                                type: 'in',
                              })}
                              className="text-xs underline"
                            >
                              Agregar stock
                            </Link>
                          );
                        })()}
                      </div>
                      {(() => {
                        const sku = it.product?.sku ?? '';
                        const skuKey = sku ? `items.${sku}` : '';
                        const messages = skuKey ? (fieldErrors[skuKey] ?? []) : [];
                        return messages.length > 0 ? (
                          <div className="mt-1 text-xs text-red-600">{messages.join(' ')}</div>
                        ) : null;
                      })()}
                    </div>
                    <div className="text-sm">
                      {it.qty} × ${Number(it.price).toFixed(2)}
                    </div>
                  </div>
                ))}
                {(!o.items || o.items.length === 0) && (
                  <div className="text-muted-foreground text-sm">No hay ítems en la orden.</div>
                )}
                {Array.isArray(fieldErrors['items']) && fieldErrors['items'].length > 0 && (
                  <div className="text-xs text-red-600">{fieldErrors['items'].join(' ')}</div>
                )}
              </div>
            </div>
          </div>
        }
      />
    </AppLayout>
  );
};

export default OrderDetailPage;
