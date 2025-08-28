# [DL] Tickets Manager for WooCommerce

**Plugin para la venta de entradas nominativas con códigos QR en WooCommerce.**  

Este plugin permite gestionar la venta de tickets para eventos directamente desde WooCommerce. Cada entrada es nominativa, con código único y validable mediante un sistema propio de lectura de QR.

---

## ✨ Características

- Compatible con **WooCommerce**.
- Nuevo tipo de producto **Ticket**.
- Generación de **códigos QR únicos** por entrada.
- Entradas **nominativas** (se solicita el nombre para cada ticket comprado).
- Configuración de **fecha de finalización** de eventos.
- Sistema integrado de **lectura y validación de tickets** mediante QR.
- Estado del ticket: **"En espera"**, **"Usado"** o **Cancelado**.

---

## 🚀 Instalación

1. Descarga el plugin.
2. Sube la carpeta al directorio `/wp-content/plugins/`.
3. Actívalo desde el panel de administración de WordPress.
4. Asegúrate de tener **WooCommerce** instalado y activo.

---

## 🛠 Uso

1. Crea un nuevo producto de tipo **Ticket** en WooCommerce.
2. Configura los detalles del evento (fecha de finalización incluida).
3. Cuando un usuario realice la compra:
   - Se generarán los tickets nominativos.
   - Cada ticket tendrá un **código QR único**.
4. Usa el sistema de validación integrado para **leer y comprobar** los QR en la entrada del evento.

---

## 📌 Requisitos

- WordPress 6.0 o superior
- WooCommerce 7.0 o superior
- PHP 8.0 o superior

---

## 📖 Notas

- El sistema de validación de QR funciona desde cualquier dispositivo con cámara (móvil, tablet o PC con webcam).
- El plugin evita que los tickets puedan reutilizarse una vez validados.

---

## Acciones disponibles

**Nombre:** dl_ticket_manager_before_ticket_name_input
**Descripción:** Se ejecuta antes de los inputs en la ficha de producto
**Parámetros:** $product, $is_show_id, $classes_input

**Nombre:** dl_ticket_manager_after_ticket_name_input
**Descripción:** Se ejecuta después de los inputs en la ficha de producto
**Parámetros:** $product, $is_show_id, $classes_input

**Nombre:** dl_validation_event
**Descripción:** Se ejecuta cada vez que se retorna en la validación del ticket.
**Parámetros:** $type, $data

**Nombre:** dl_ticket_manager_email_ticket_details_before
**Descripción:** Se ejecuta antes de mostrar los detalles del evento en el email.
**Parámetros:** $ticket, $order

**Nombre:** dl_ticket_manager_email_ticket_details_after
**Descripción:** Se ejecuta después de mostrar los detalles del evento en el email.
**Parámetros:** $ticket, $order

**Nombre:** dl_ticket_manager_ticket_created
**Descripción:** Se ejecuta cuando se crea un ticket
**Parámetros:** $post_id, $data

**Nombre:** dl_ticket_manager_ticket_status_changed
**Descripción:** Se ejecuta cuando se cambia de estado un ticket
**Parámetros:** $ticket_id, $data

**Nombre:** dl_ticket_manager_ticket_status_changed
**Descripción:** Se ejecuta antes de mostrar los campos de configuración en la edición del producto
**Parámetros:** $ticket_id, $data

**Nombre:** dl_ticket_event_fields_after
**Descripción:** Se ejecuta después de mostrar los campos de configuración en la edición del producto

**Nombre:** dl_ticket_save_event_fields
**Descripción:** Se ejecuta cuando se guardan los campos de un ticket
**Parámetros:** $post_id

## Filtros disponibles

**Nombre:** dl_ticket_manager_templates
**Descripción:** Filtra el listado de plantillas PDF
**Parámetros:** Array

**Nombre:** dl_ticket_manager_input_classes
**Descripción:** Filtra las clases que reciben los inputs que se muestran en la ficha de un producto
**Parámetros:** String

**Nombre:** dl_validate_ticket_data
**Descripción:** Filtra un valor que se tendrá en cuenta a la hora de validar un ticket. Si el ticket no es correcto, debe retornar un WP_Error
**Parámetros:** $ticket_data, $order_id, $data

**Nombre:** dl_ticket_manager_generate_code
**Descripción:** Filtra el código único generado para los tickets
**Parámetros:** String

**Nombre:** dl_ticket_manager_create_ticket_data
**Descripción:** Filtra el contenido de un ticket cuando se va a crear
**Parámetros:** Array

**Nombre:** dl_ticket_manager_create_ticket
**Descripción:** Filtra el contenido de un ticket cuando se va a crear usando la funcion wp_insert_post
**Parámetros:** Array

**Nombre:** dl_ticket_manager_get_ticket_data
**Descripción:** Filtra el contenido de un ticket cuando se obtiene
**Parámetros:** Array

**Nombre:** dl_ticket_manager_qr_data
**Descripción:** Filtra los datos que se usan para crear el código QR
**Parámetros:** Array

**Nombre:** dl_ticket_manager_dompdf_options
**Descripción:** Filtra las opciones de Dompdf para generar el ticket
**Parámetros:** Options

**Nombre:** dl_ticket_manager_pdf_template_vars
**Descripción:** Filtra los datos que se le envian a la plantilla para crear el ticket en PDF
**Parámetros:** Array

**Nombre:** dl_ticket_manager_dompdf
**Descripción:** Filtra el objeto Dompdf
**Parámetros:** Dompdf

**Nombre:** dl_ticket_purchasable
**Descripción:** Filtra una variable para definir si un producto puede ser comprado o no
**Parámetros:** $purchasable Boolean, $product

---

## Autor

Desarrollado por [Daniel Lúcia](https://daniellucia.es)  
