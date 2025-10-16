import { useCallback, useEffect, useRef, useState } from 'react';

interface UseImageUploadProps {
  onUpload?: (url: string, file: File) => void;
  initialFile?: File | string | null;
}

export function useImageUpload({ onUpload, initialFile }: UseImageUploadProps = {}) {
  const previewRef = useRef<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [fileName, setFileName] = useState<string | null>(null);

  useEffect(() => {
    if (typeof initialFile === 'string') {
      setPreviewUrl(initialFile);
      setFileName(initialFile.split('/').pop() ?? 'Archivo');
    } else if (initialFile instanceof File) {
      const url = URL.createObjectURL(initialFile);
      setPreviewUrl(url);
      setFileName(initialFile.name);
      previewRef.current = url;
    }
  }, [initialFile]);

  const handleThumbnailClick = useCallback(() => {
    fileInputRef.current?.click();
  }, []);

  const handleFileChange = useCallback(
    (event: React.ChangeEvent<HTMLInputElement>) => {
      const file = event.target.files?.[0];
      if (file) {
        setFileName(file.name);
        const url = URL.createObjectURL(file);
        setPreviewUrl(url);
        previewRef.current = url;
        onUpload?.(url, file);
      }
    },
    [onUpload],
  );

  const handleRemove = useCallback(() => {
    if (previewUrl?.startsWith('blob:')) {
      URL.revokeObjectURL(previewUrl);
    }
    setPreviewUrl(null);
    setFileName(null);
    previewRef.current = null;
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, [previewUrl]);

  useEffect(() => {
    return () => {
      if (previewRef.current) {
        URL.revokeObjectURL(previewRef.current);
      }
    };
  }, []);

  return {
    previewUrl,
    fileName,
    fileInputRef,
    handleThumbnailClick,
    handleFileChange,
    handleRemove,
  };
}
