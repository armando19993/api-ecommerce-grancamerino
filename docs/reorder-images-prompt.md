# Implementar reordenamiento de imágenes en edición de productos

Necesito que implementes el reordenamiento de imágenes en la pantalla de edición de productos. El backend ya tiene todo listo.

## Contexto de la API

Cada imagen de producto tiene esta forma:

```json
{
  "id": "uuid",
  "url": "https://...",
  "is_primary": true,
  "sort_order": 0
}
```

Las imágenes ya vienen ordenadas por `sort_order` en cualquier endpoint de producto (`GET /api/products/:id`). La primera (`sort_order: 0`) es siempre la principal.

---

## Endpoint para reordenar

```
PATCH /api/products/:productId/images/reorder
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "image_ids": ["uuid-1", "uuid-2", "uuid-3"]
}
```

El orden del array define el orden visual. El primer ID queda como imagen principal automáticamente. No hay que llamar a ningún otro endpoint para cambiar la principal, este solo lo hace todo.

**Respuesta exitosa `200`:**
```json
{
  "status": "success",
  "message": "Images reordered successfully",
  "data": [
    { "id": "uuid-1", "url": "...", "is_primary": true,  "sort_order": 0 },
    { "id": "uuid-2", "url": "...", "is_primary": false, "sort_order": 1 },
    { "id": "uuid-3", "url": "...", "is_primary": false, "sort_order": 2 }
  ]
}
```

---

## Lo que necesito en la UI

En el formulario de edición de producto, donde se muestran las imágenes actuales:

1. Renderizar las imágenes como tarjetas arrastrables (drag & drop), ordenadas por `sort_order`
2. La primera tarjeta debe tener un badge visible que diga **"Principal"**
3. Al soltar una imagen en nueva posición, reconstruir el array de IDs en el nuevo orden y llamar al endpoint `PATCH /reorder`
4. Mientras se guarda, mostrar estado de carga en las tarjetas
5. Al recibir la respuesta, actualizar el estado local con el array `data` que devuelve el endpoint
6. En caso de error, revertir al orden anterior (optimistic update con rollback)

**Librería sugerida para drag & drop:** `@dnd-kit/core` + `@dnd-kit/sortable` (React) o `vuedraggable` (Vue). Si ya hay otra librería de DnD instalada en el proyecto, úsala.

---

## Flujo resumido

```
usuario arrastra imagen
  → reordena array local (optimistic update)
  → PATCH /api/products/:id/images/reorder  { image_ids: [...] }
  → éxito  → actualizar estado con respuesta del servidor
  → error  → revertir al orden anterior + mostrar mensaje de error
```

---

## Todos los endpoints de imágenes disponibles

| Acción | Método | URL |
|---|---|---|
| Ver producto con imágenes | `GET` | `/api/products/:id` |
| Agregar imagen | `POST` | `/api/products/:id/images` |
| Eliminar imagen | `DELETE` | `/api/products/:id/images/:imageId` |
| Reordenar | `PATCH` | `/api/products/:id/images/reorder` |
