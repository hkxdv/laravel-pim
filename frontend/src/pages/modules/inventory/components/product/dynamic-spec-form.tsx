import InputError from '@/components/input-error';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  type FileRef,
  isProductSpecDefinition,
  type ProductMetadataValues,
  type ProductSpecDefinition,
  type SpecField,
} from '@/types/product-spec';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';

interface DynamicSpecFormProps {
  metadataJson: string | null;
  onMetadataChange: (nextJson: string | null) => void;
  errors?: Record<string, string>;
}

type SpecSlug = 'screens' | 'batteries' | 'flex_connectors';
const AVAILABLE_SPECS: { label: string; value: SpecSlug }[] = [
  { label: 'Pantallas', value: 'screens' },
  { label: 'Baterías', value: 'batteries' },
  { label: 'Flex y Conectores', value: 'flex_connectors' },
];

function safeParseMetadata(input: string | null): ProductMetadataValues {
  if (!input) return {};
  try {
    const obj: unknown = JSON.parse(input);
    if (obj && typeof obj === 'object') return obj as ProductMetadataValues;
    return {};
  } catch {
    return {};
  }
}

export default function DynamicSpecForm({
  metadataJson,
  onMetadataChange,
  errors,
}: Readonly<DynamicSpecFormProps>) {
  const initialValues = useMemo(() => safeParseMetadata(metadataJson), [metadataJson]);
  const initialSlug: SpecSlug | null =
    (initialValues['_spec_slug'] as SpecSlug | undefined) ?? null;

  const [slug, setSlug] = useState<SpecSlug | null>(initialSlug);
  const [definition, setDefinition] = useState<ProductSpecDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [values, setValues] = useState<ProductMetadataValues>(initialValues);

  useEffect(() => {
    // Sync from external metadata only if content differs to avoid loops
    const newValues = initialValues;
    const newJson = JSON.stringify(newValues);
    const curJson = JSON.stringify(values);
    if (newJson !== curJson) {
      setValues(newValues);
    }
  }, [metadataJson, initialValues, values]);

  useEffect(() => {
    if (!slug) {
      setDefinition(null);
      setError(null);
      return;
    }
    setLoading(true);
    setError(null);
    axios
      .get(`/api/v1/inventory/spec-definition/${slug}`)
      .then((res) => {
        const data = res.data as unknown;
        if (isProductSpecDefinition(data)) {
          setDefinition(data);
        } else {
          setError('Definición inválida');
          setDefinition(null);
        }
      })
      .catch((error_: unknown) => {
        const msg = error_ instanceof Error ? error_.message : String(error_);
        setError(msg || 'Error al cargar definición');
        setDefinition(null);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [slug]);

  useEffect(() => {
    // Propagate changes only when JSON differs to prevent update loops
    const next: ProductMetadataValues = { ...values };
    if (slug) next['_spec_slug'] = slug;
    else delete next['_spec_slug'];
    const nextJson = JSON.stringify(next);
    if (nextJson !== metadataJson) {
      onMetadataChange(nextJson);
    }
  }, [values, slug, onMetadataChange, metadataJson]);

  type MetaValue = string | number | boolean | FileRef;
  const handleFieldChange = (field: SpecField, raw: unknown) => {
    const key = field.id;
    const typedRaw = raw as MetaValue;
    setValues((prev) => ({ ...prev, [key]: typedRaw }));
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Especificaciones dinámicas</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label htmlFor="spec_slug">Tipo de producto</Label>
            <select
              id="spec_slug"
              value={slug ?? ''}
              onChange={(e) => {
                const raw = e.target.value;
                setSlug(raw ? (raw as SpecSlug) : null);
              }}
              className="border-input bg-background mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none"
            >
              <option value="">Seleccione…</option>
              {AVAILABLE_SPECS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
            <InputError message={errors?.['metadata._spec_slug'] ?? ''} className="mt-1" />
          </div>
          <div className="flex items-end">
            {loading && <span className="text-muted-foreground text-sm">Cargando definición…</span>}
            {!loading && error && <span className="text-sm text-red-600">{error}</span>}
          </div>
        </div>

        {definition && (
          <div className="space-y-6">
            {definition.definition.spec_groups.map((group, gi) => (
              <div key={`group-${gi}`} className="space-y-3">
                <h4 className="text-sm font-semibold">{group.group_title}</h4>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  {group.fields.map((field) => {
                    const val = values[field.id];
                    const req = field.constraints?.required ?? false;
                    const errMsg = errors?.[`metadata.${field.id}`];
                    const commonLabel = (
                      <Label htmlFor={field.id}>
                        {field.label}
                        {req ? ' *' : ''}
                        {field.unit ? ` (${field.unit})` : ''}
                      </Label>
                    );

                    switch (field.type) {
                      case 'text': {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <Input
                              id={field.id}
                              value={typeof val === 'string' ? val : ''}
                              placeholder={field.placeholder}
                              onChange={(e) => {
                                handleFieldChange(field, e.target.value);
                              }}
                            />
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                      case 'number': {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <Input
                              id={field.id}
                              type="number"
                              value={typeof val === 'number' ? val : ''}
                              onChange={(e) => {
                                handleFieldChange(field, Number(e.target.value));
                              }}
                            />
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                      case 'textarea': {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <textarea
                              id={field.id}
                              className="border-input bg-background mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none"
                              value={typeof val === 'string' ? val : ''}
                              placeholder={field.placeholder}
                              onChange={(e) => {
                                handleFieldChange(field, e.target.value);
                              }}
                            />
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                      case 'select': {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <select
                              id={field.id}
                              value={typeof val === 'string' ? val : ''}
                              onChange={(e) => {
                                handleFieldChange(field, e.target.value);
                              }}
                              className="border-input bg-background mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none"
                            >
                              <option value="">Seleccione…</option>
                              {field.options?.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                  {opt.label}
                                </option>
                              ))}
                            </select>
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                      case 'checkbox': {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <div className="mt-2 flex items-center gap-2">
                              <Switch
                                id={field.id}
                                checked={typeof val === 'boolean' ? val : false}
                                onCheckedChange={(checked) => {
                                  handleFieldChange(field, checked);
                                }}
                              />
                            </div>
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                      default: {
                        return (
                          <div key={field.id}>
                            {commonLabel}
                            <InputError message={errMsg ?? ''} className="mt-1" />
                          </div>
                        );
                      }
                    }
                  })}
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
