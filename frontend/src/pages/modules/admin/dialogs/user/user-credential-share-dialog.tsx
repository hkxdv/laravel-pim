import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import { Check, Copy, Mail, Share } from 'lucide-react';
import { useState } from 'react';

interface CredentialShareDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onFinish: () => void;
  userName: string;
  userEmail: string;
  password: string;
}

/**
 * Componente de diálogo para compartir credenciales generadas
 */
const CredentialShareDialog = ({
  isOpen,
  onClose,
  onFinish,
  userName,
  userEmail,
  password,
}: CredentialShareDialogProps) => {
  const { showSuccess } = useToastNotifications();
  const [activeTab, setActiveTab] = useState<string>('clipboard');
  const [emailSubject, setEmailSubject] = useState<string>(`Acceso al sistema - ${userName}`);
  const [emailTo, setEmailTo] = useState<string>(userEmail);

  // Función para copiar al portapapeles
  const copyToClipboard = (text: string) => {
    void navigator.clipboard.writeText(text).then(() => {
      showSuccess('Información copiada al portapapeles');
    });
  };

  // Generar el mensaje de credenciales
  const credentialMessage = `Estimado/a ${userName},

Se ha creado una cuenta para ti en el sistema con las siguientes credenciales:

Usuario: ${userEmail}
Contraseña: ${password}

Por favor, verifica tu correo electrónico y inicia sesión para cambiar tu contraseña.`;

  // Generar el enlace de correo electrónico
  const getMailtoLink = () => {
    const subject = encodeURIComponent(emailSubject);
    const body = encodeURIComponent(credentialMessage);
    return `mailto:${emailTo}?subject=${subject}&body=${body}`;
  };

  return (
    <Dialog
      open={isOpen}
      onOpenChange={(open) => {
        if (!open) {
          onClose();
        }
      }}
    >
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Compartir credenciales</DialogTitle>
          <DialogDescription>
            La cuenta de usuario ha sido creada exitosamente. Comparte las credenciales con el
            usuario.
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="clipboard" value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="clipboard">
              <Copy className="mr-2 h-4 w-4" />
              Copiar
            </TabsTrigger>
            <TabsTrigger value="email">
              <Mail className="mr-2 h-4 w-4" />
              Correo
            </TabsTrigger>
          </TabsList>

          <TabsContent value="clipboard" className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="credentials">Mensaje de credenciales</Label>
              <Textarea id="credentials" readOnly value={credentialMessage} rows={8} />
              <Button
                variant="outline"
                className="w-full"
                onClick={() => {
                  copyToClipboard(credentialMessage);
                }}
              >
                <Copy className="mr-2 h-4 w-4" />
                Copiar al portapapeles
              </Button>
            </div>
          </TabsContent>

          <TabsContent value="email" className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email-to">Enviar a</Label>
              <Input
                id="email-to"
                type="email"
                value={emailTo}
                onChange={(e) => {
                  setEmailTo(e.target.value);
                }}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email-subject">Asunto</Label>
              <Input
                id="email-subject"
                value={emailSubject}
                onChange={(e) => {
                  setEmailSubject(e.target.value);
                }}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email-body">Mensaje</Label>
              <Textarea id="email-body" value={credentialMessage} rows={6} readOnly />
            </div>
          </TabsContent>
        </Tabs>

        <DialogFooter className="flex-col space-y-3 sm:space-y-0 sm:space-x-3">
          {activeTab === 'email' ? (
            <Button className="w-full sm:w-auto" asChild>
              <a href={getMailtoLink()} target="_blank" rel="noopener noreferrer">
                <Mail className="mr-2 h-4 w-4" />
                Enviar correo
              </a>
            </Button>
          ) : (
            <Button
              variant="secondary"
              className="w-full sm:w-auto"
              onClick={() => {
                const credentials = `Usuario: ${userEmail}\nContraseña: ${password}`;
                copyToClipboard(credentials);
              }}
            >
              <Share className="mr-2 h-4 w-4" />
              Copiar solo credenciales
            </Button>
          )}

          <Button variant="default" className="w-full sm:w-auto" onClick={onFinish}>
            <Check className="mr-2 h-4 w-4" />
            Finalizar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default CredentialShareDialog;
