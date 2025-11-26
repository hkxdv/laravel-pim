export type SpecFieldType =
  | 'text'
  | 'number'
  | 'select'
  | 'checkbox'
  | 'textarea'
  | 'date'
  | 'file';

export interface SpecOption {
  label: string;
  value: string;
}

export interface SpecConstraints {
  required?: boolean;
  min?: number;
  max?: number;
  pattern?: string;
  max_size_kb?: number;
}

export interface SpecField {
  id: string;
  type: SpecFieldType;
  label: string;
  placeholder?: string;
  unit?: string;
  options?: SpecOption[];
  multiple?: boolean;
  accept?: string;
  constraints?: SpecConstraints;
}

export interface SpecGroup {
  group_title: string;
  fields: SpecField[];
}

export interface ProductSpecDefinition {
  definition: {
    category: string;
    template_version: string;
    spec_groups: SpecGroup[];
  };
}

export interface FileRef {
  url: string;
  name: string;
  mime?: string;
  size_kb?: number;
}

export type ProductMetadataValues = Record<string, string | number | boolean | FileRef>;

export function isProductSpecDefinition(input: unknown): input is ProductSpecDefinition {
  if (!input || typeof input !== 'object') return false;
  const obj = input as Record<string, unknown>;
  const def = obj['definition'];
  if (!def || typeof def !== 'object') return false;
  const defObj = def as Record<string, unknown>;
  return (
    typeof defObj['category'] === 'string' &&
    typeof defObj['template_version'] === 'string' &&
    Array.isArray(defObj['spec_groups'])
  );
}
