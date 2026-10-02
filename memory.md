# Memoria del proyecto

> Leer este archivo al inicio de cada sesión para no perder el contexto del proyecto.

## Quién soy

**Diego Romeo.** Mi negocio es **VNA — Vía Navegable Argentina**.

## Qué estamos construyendo

**Gestión de Modernización Tecnológica – VNT**, una herramienta para gestionar
los mantenimientos del equipamiento instalado en la Vía Navegable Troncal
(VNT): mantenimientos **preventivos**, **correctivos** y **evolutivos**.

Categorías de equipamiento que cubre: Centro de Monitoreo, Punto de
Monitoreo Remoto, Equipamiento de Campo, Boyas Multiparamétricas y SiMon.

## Estado actual

- Implementación actual: `mantenimientos_vnt.html` — archivo único,
  autocontenido, sin dependencias externas (HTML/CSS/JS embebidos, logo VNA
  en base64, datos persistidos en `localStorage` del navegador).
- Funcionalidades ya resueltas: alta/edición/baja de mantenimientos
  (Preventivo, Correctivo o **Evolutivo**), calendario mensual con doble
  clic para ver/editar o crear en una fecha, listado filtrable, descarga de
  reporte en PDF vía impresión del navegador.
- Pestaña **Catálogos**: gestión de alta/baja/modificación de proveedores y
  de tipos de equipamiento. Los combos "Proveedor" y "Tipo de equipamiento"
  del formulario se alimentan de estos catálogos (ya no son texto libre ni
  una lista fija), con un botón "+" para cargar un ítem nuevo sin salir del
  formulario. Cada tipo de equipamiento puede tener un proveedor habitual
  que se autocompleta al elegirlo. Renombrar actualiza en cascada los
  mantenimientos existentes; eliminar avisa cuántos registros lo usan pero
  no borra esos datos históricos.
- Trabajo en curso en la rama `claude/vnt-maintenance-form-plt36o`,
  [PR #1](https://github.com/romeodiego/Repositorio/pull/1).
- Pendiente de definir: si se necesita una bitácora realmente compartida
  entre varios usuarios (hoy los datos quedan en el navegador de cada uno,
  no en un backend común).

## Preferencias de diseño

- La paleta de colores debe inspirarse en el **logo de VNA** (azules/marino
  oscuro, en línea con los tonos ya usados en la interfaz actual).
- El enfoque es iterativo: seguir refinando la herramienta sesión a sesión
  hasta llegar a una solución sólida y prolija, no quedarse con la primera
  versión.
