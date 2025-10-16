import axios from 'axios';

/**
 * Obtiene un nuevo token CSRF del backend llamando al endpoint de Sanctum.
 * Esta acción establece la cookie `XSRF-TOKEN`, que Axios utilizará automáticamente
 * en solicitudes posteriores para establecer el encabezado `X-XSRF-TOKEN`.
 *
 * La función es `async`, por lo que cualquier error en la solicitud `axios.get`
 * se propagará como una promesa rechazada, que puede ser manejada por quien la llame.
 *
 * @throws Lanza un error si la solicitud para obtener el token falla.
 */
export const getCSRFToken = async (): Promise<void> => {
  // La URL base se configura en `http.ts`, por lo que podemos usar una ruta relativa.
  // El bloque try/catch es innecesario aquí, ya que async/await propaga
  // automáticamente los errores de la promesa.
  await axios.get('/sanctum/csrf-cookie');
};
