# WooCommerce Payment Recovery

<div align="center">

**Plugin para recuperar pagos fallidos o pendientes en WooCommerce mediante correos de recordatorio automáticos**

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](./LICENSE)
![WordPress 5.0+](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![WooCommerce 3.0+](https://img.shields.io/badge/WooCommerce-3.0%2B-green)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-orange)

</div>

## 📋 Descripción

**WooCommerce Payment Recovery** es un plugin que ayuda a recuperar pedidos abandonados enviando correos automáticos de recordatorio cuando un cliente no completa el pago. 

### Características

✅ **3 correos de recordatorio automáticos** con tiempos configurables  
✅ **Cancelación automática** de órdenes impagadas  
✅ **Botones dinámicos** para completar pago y restaurar carrito  
✅ **Plantillas profesionales** con estilos de WooCommerce  
✅ **Totalmente configurable** desde admin de WordPress  
✅ **Logs detallados** para debugging  
✅ **Compatible con variable products** y productos con variaciones  
✅ **Integración con ActionScheduler** para ejecución confiable  

---

## 🔧 Requisitos

- **WordPress:** 5.0 o superior
- **PHP:** 7.4 o superior  
- **WooCommerce:** 3.0 o superior
- **ActionScheduler:** Incluido en WooCommerce 7.0+, o instálalo como plugin

### Verificar dependencias

1. Ve a **WooCommerce > Estado del sitio**
2. Busca "ActionScheduler" - debe mostrar "✅ Disponible"
3. Si no aparece, instala el plugin `Action Scheduler` desde el repositorio

---

## 📦 Instalación

### Opción 1: Descarga manual

1. Descarga el plugin desde GitHub
2. Ve a **Plugins > Añadir nuevo > Subir plugin**
3. Sube el archivo `.zip`
4. Activa el plugin

### Opción 3: Través de SFTP

```bash
# Descarga el repositorio
git clone https://github.com/juanmderosa/wc-payment-recovery.git

# Sube a tu servidor
sftp user@tuservidor.com
put -r wc-payment-recovery /wp-content/plugins/
```

---

## ⚙️ Configuración

### Configuración Básica

1. Ve a **WooCommerce > Payment Recovery**
2. Ajusta los siguientes parámetros:

| Opción | Valor por defecto | Descripción |
|--------|------------------|-------------|
| Email 1 Activado | ✅ Sí | Enviar primer recordatorio |
| Email 1 Retraso | 2 minutos | Tiempo hasta enviar email 1 |
| Email 2 Activado | ✅ Sí | Enviar segundo recordatorio |
| Email 2 Retraso | 5 minutos | Tiempo hasta enviar email 2 |
| Email 3 Activado | ✅ Sí | Enviar tercer recordatorio |
| Email 3 Retraso | 10 minutos | Tiempo hasta enviar email 3 |
| Cancelación Automática | ✅ Sí | Cancelar orden si sigue impaga |
| Retraso de Cancelación | 30 minutos | Tiempo hasta cancelar |

### Personalización de Correos

Ve a **WooCommerce > Configuración > Correos** para:

- Cambiar el asunto de cada correo
- Personalizar el contenido
- Habilitar/deshabilitar cada tipo

---

## 🧪 Casos de Prueba

### Test 1: Verificar que el plugin se carga

**Objetivo:** Confirmar que el plugin está activo y funcionando

**Pasos:**

1. Ve a **Plugins** y verifica que "WooCommerce Payment Recovery" está activo
2. Ve a **WooCommerce > Payment Recovery**
3. Deberías ver el panel de configuración

**Resultado esperado:** ✅ El panel carga sin errores y muestras las opciones

---

### Test 2: Crear una orden de prueba impaga

**Objetivo:** Generar una orden en estado "pending" para que el plugin la procese

**Pasos:**

1. Ve al **frontend** de tu tienda
2. Añade un producto al carrito
3. Procede al pago
4. En la página de pago, **NO completes** el pago (cierra la pestaña, o espera timeout)
5. Verifica que la orden quedó en estado **"Pending"** (WooCommerce > Pedidos)

**Resultado esperado:** ✅ La orden aparece con estado "pending" o "failed"

---

### Test 3: Verificar que se programan las acciones

**Objetivo:** Confirmar que el plugin crea las acciones programadas en ActionScheduler

**Pasos:**

1. Crea una orden de prueba (Test 2)
2. Ve a **Herramientas > Acciones programadas** (si existe)
3. Busca acciones con grupo "wc-payment-recovery"
4. Deberías ver ~4 acciones pendientes para esa orden

**Alternativa:** Revisa los logs en `wp-content/debug.log`:
```
[WCPR] Email 1 programado
[WCPR] Email 2 programado
[WCPR] Email 3 programado
[WCPR] Cancelación automática programada
```

**Resultado esperado:** ✅ Las 4 acciones aparecen programadas o en logs

---

### Test 4: Verificar envío del Email 1

**Objetivo:** Confirmar que se envía el primer correo de recordatorio

**Pasos:**

1. Crea una orden de prueba (Test 2)
2. Espera 2 minutos (o el tiempo configurado)
3. Revisa el correo en tu cliente de email
4. Verifica que contiene:
   - Saludo con nombre del cliente
   - Mensaje de recuperación
   - Tabla con productos
   - Total del pedido
   - Botón "Completar pago"
   - Botón "Volver al carrito"
   - Tiempo de reserva

**Alternativa:** Si ActionScheduler no ejecuta automáticamente:
- Ejecuta este comando en CLI:
  ```bash
  wp actionscheduler run --hook=wcpr_send_email_1 --force
  ```

**Resultado esperado:** ✅ Email 1 llega a la bandeja de entrada con formato correcto

---

### Test 5: Probar botón "Completar pago"

**Objetivo:** Verificar que el botón dirija correctamente a checkout

**Pasos:**

1. Abre el Email 1 que recibiste
2. Haz clic en **"Completar pago"**
3. Deberías ir a la página de checkout con la orden pre-cargada
4. Completa el pago con tarjeta de prueba

**Resultado esperado:** ✅ El pago se procesa y la orden cambia a "processing"

---

### Test 6: Probar botón "Volver al carrito"

**Objetivo:** Verificar que se restaura el carrito correctamente

**Pasos:**

1. Abre el Email 1 que recibiste
2. Haz clic en **"Volver al carrito"**
3. Deberías ver el carrito con los productos originales (incluyendo variaciones)
4. Verifica que aparecen:
   - Nombre del producto
   - Cantidad
   - Opciones/variaciones (si las hay)
   - Total correcto

**Resultado esperado:** ✅ El carrito se restaura con todos los items y variaciones intactas

---

### Test 7: Verificar Email 2 y 3

**Objetivo:** Confirmar que se envían los correos de recordatorio posteriores

**Pasos:**

1. Crea una orden de prueba (Test 2)
2. Espera 5 minutos → Verifica Email 2
3. Espera 10 minutos → Verifica Email 3
4. En cada correo verifica que contiene el mensaje correspondiente

**Resultado esperado:** ✅ Email 2 y Email 3 llegan en los tiempos configurados

---

### Test 8: Verificar cancelación automática

**Objetivo:** Confirmar que se cancela automáticamente después del tiempo configurado

**Pasos:**

1. Crea una orden de prueba (Test 2)
2. Nota el ID y estado actual
3. Espera el tiempo de cancelación (30 min por defecto)
4. Ve a **WooCommerce > Pedidos**
5. Abre la orden y verifica que está en estado **"Cancelled"**
6. Revisa que tenga una nota: "Pedido cancelado por falta de pago"

**Resultado esperado:** ✅ La orden se cancela automáticamente después del tiempo configurado

---

### Test 9: Verificar email de cancelación

**Objetivo:** Confirmar que se envía email cuando se cancela

**Pasos:**

1. Crea una orden de prueba (Test 2)
2. Espera 30 minutos (o tiempo configurado)
3. Revisa tu email
4. Verifica que el correo de cancelación contiene:
   - Mensaje de cancelación
   - Tabla con productos
   - Total
   - NO contiene botones de pago

**Resultado esperado:** ✅ Se recibe email de cancelación con contenido correcto

---

### Test 10: Probar con productos variables

**Objetivo:** Verificar que funciona correctamente con variaciones (talla, color, etc.)

**Pasos:**

1. Selecciona un producto variable (ej: "Jean" con talla M, color azul)
2. Selecciona las variaciones específicas
3. Añade al carrito y procede a crear orden impaga
4. Espera Email 1
5. Abre Email 1 y haz clic en **"Volver al carrito"**
6. Verifica que el carrito muestra el producto CON las variaciones seleccionadas
7. Intenta modificar cantidades - el producto no debe pedir "elegir opciones"

**Resultado esperado:** ✅ El producto con variaciones se restaura correctamente sin pedir opciones

---

### Test 11: Verificar logs (si WP_DEBUG está activo)

**Objetivo:** Validar que el plugin registra eventos correctamente

**Pasos:**

1. Asegúrate que `WP_DEBUG` está activo en `wp-config.php`
2. Crea una orden de prueba
3. Ve a **Servidor > wp-content/debug.log** (vía SFTP)
4. Busca logs con `[WCPR]`

**Logs esperados:**
```
[WCPR] === WCPR PLUGIN INICIADO ===
[WCPR] ✓ HOOK CHECKOUT DISPARADO
[WCPR] Email 1 programado
[WCPR] Email 2 programado
[WCPR] Email 3 programado
[WCPR] Cancelación automática programada
```

**Resultado esperado:** ✅ Aparecen todos los logs de inicialización y programación

---

### Test 12: Desactivar y reactivar el plugin

**Objetivo:** Verificar que el plugin se carga correctamente después de reactivar

**Pasos:**

1. Ve a **Plugins**
2. Haz clic en **Desactivar** en "WooCommerce Payment Recovery"
3. Espera 3 segundos
4. Haz clic en **Activar**
5. Verifica que aparece el panel de configuración sin errores

**Resultado esperado:** ✅ Se activa sin errores y mantiene la configuración

---

## 📊 Checklist de Testing

| Test | Completado | Notas |
|------|-----------|-------|
| Test 1: Plugin carga | ☐ | |
| Test 2: Orden impaga | ☐ | |
| Test 3: Acciones programadas | ☐ | |
| Test 4: Email 1 | ☐ | |
| Test 5: Botón completar pago | ☐ | |
| Test 6: Botón carrito | ☐ | |
| Test 7: Email 2 y 3 | ☐ | |
| Test 8: Cancelación automática | ☐ | |
| Test 9: Email cancelación | ☐ | |
| Test 10: Productos variables | ☐ | |
| Test 11: Logs | ☐ | |
| Test 12: Reactivar | ☐ | |

---

## 🔍 Debugging

### Habilitar logs

1. Edita `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Los logs aparecerán en `wp-content/debug.log`

### Buscar logs específicos

```bash
# Mostrar últimos logs del plugin
tail -100 wp-content/debug.log | grep WCPR

# Mostrar solo errores
grep -i error wp-content/debug.log | grep WCPR
```

### Ejecutar acciones manualmente (WP-CLI)

```bash
# Enviar Email 1 para orden específica
wp eval 'do_action("wcpr_send_email_1", 12345);'

# Cancelar orden específica
wp eval 'do_action("wcpr_cancel_order", 12345);'

# Ver acciones programadas
wp actionscheduler list --group=wc-payment-recovery
```

---

## ❌ Solución de Problemas

### "ActionScheduler no disponible"

**Causa:** WooCommerce < 7.0 sin ActionScheduler

**Solución:**
1. Actualiza WooCommerce a 7.0+, O
2. Instala el plugin "Action Scheduler" desde WordPress.org

---

### Los correos no se envían

**Checklist:**

- [ ] ¿ActionScheduler está disponible? (WooCommerce > Estado)
- [ ] ¿El plugin está activo?
- [ ] ¿Los emails están habilitados en WooCommerce > Configuración > Correos?
- [ ] ¿La orden está en estado "pending" o "failed"?
- [ ] ¿El servidor ejecuta tareas cron? (Verifica con `curl https://tutienda.com/wp-cron.php`)
- [ ] ¿WP_DEBUG está activo? (Revisa debug.log para errores)

---

### El correo de cancelación no se envía

**Checklist:**

- [ ] ¿ActionScheduler está disponible? (WooCommerce > Estado)
- [ ] ¿El plugin está activo?
- [ ] ¿Los emails están habilitados en WooCommerce > Configuración > Correos?
- [ ] ¿La orden está en estado "pending" o "failed"?
- [ ] Ve a Woocommerce > Ajustes > Productos > Intentario y pon "Reservar en inventario (en minutos)" con un valor 1 minuto superior al e-mail de cancelación.
- [ ] ¿El servidor ejecuta tareas cron? (Verifica con `curl https://tutienda.com/wp-cron.php`)
- [ ] ¿WP_DEBUG está activo? (Revisa debug.log para errores)

---

### Las órdenes no se cancelan

**Checklist:**

- [ ] ¿La cancelación automática está habilitada?
- [ ] ¿Ya pasó el tiempo configurado?
- [ ] ¿La orden sigue en estado "pending"? (Si cambió a "processing", no se cancela)
- [ ] ¿Hay un hook que impide la cancelación?

---

### "Por favor, elige las opciones del producto" al volver al carrito

**Causa:** Las variaciones no se capturaron correctamente

**Solución:**
- Asegúrate que estás usando la versión más reciente
- Vacía cache del servidor
- Intenta desde navegador incógnito

---

## 📝 Logs de Ejemplo

### Inicio correcto del plugin

```
[12-Mar-2026 10:00:00 UTC] [WCPR] === WCPR PLUGIN INICIADO ===
[12-Mar-2026 10:00:01 UTC] [WCPR] ✓ HOOK CHECKOUT DISPARADO | Array ( [order_id] => 12345 )
[12-Mar-2026 10:00:02 UTC] [WCPR] Email 1 programado | Array ( [order_id] => 12345 [delay] => 2 )
[12-Mar-2026 10:00:03 UTC] [WCPR] Email 2 programado | Array ( [order_id] => 12345 [delay] => 5 )
```

### Error común

```
[12-Mar-2026 10:00:00 UTC] [WCPR] ERROR: ActionScheduler no disponible
```

---

## 📄 Licencia

GPL v2 

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nombre`)
3. Commit tus cambios (`git commit -am 'Add feature'`)
4. Push a la rama (`git push origin feature/nombre`)
5. Abre un Pull Request

---

## 📧 Soporte

Para reportar bugs o sugerencias, abre un issue en GitHub o contactame a juanmderosa@gmail.com.

---

**Última actualización:** 12 de marzo de 2026  
**Versión:** 1.0.1
