# 🌳 API REST de Árbol de Nodos (Node Tree)

Este proyecto implementa una API RESTful para la gestión de una **estructura de árbol de nodos** en **Laravel 10**. Sigue el patrón **Repository/Service** y utiliza **Form Requests** para una validación estricta de todas las peticiones.

La funcionalidad de **profundidad** (`depth > 1`) se implementa mediante **recursividad en PHP** como una solución viable sin depender de librerías de Nested Set.

## 1. Requisitos del Sistema 📋

Asegúrate de tener instalado:

* **PHP:** Versión `8.1` o superior
* **Composer:** Gestor de dependencias
* **Base de Datos:** MySQL (recomendado) o MariaDB
* **Git:** Para clonar el repositorio

## 2. Instalación y Configuración 🚀

Sigue estos pasos para poner en marcha el proyecto:

## 2.1. Clonar el Repositorio

```bash
git clone https://github.com/idelsangithub/node-tree-api.git
cd node-tree-api

2.2. Instalar Dependencias
composer install

2.3. Configurar el Entorno
    * Copia el archivo de configuración: cp .env.example .env
    * Genera la clave de aplicación:php artisan key:generate
### 3. Configura la Base de Datos Edita el archivo .env y rellena las credenciales (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
Ejecuta: php artisan migrate:fresh --seed   --Esto creará las tablas (nodes, node_translations) y precargará una estructura de árbol inicial con traducciones

Acceder a la Documentación (Swagger UI)
ejecuta php artisan l5-swagger:generate
Accede: http://127.0.0.1:8000/api/documentation

Endpoints
POST	/nodes	Crea un nuevo nodo.
GET	/nodes/roots	Lista nodos raíz, aplicando paginación
GET	/nodes/{id}/children	Lista los descendientes de {id} hasta la profundidad especificada (depth)
DELETE	/nodes/{id}	Elimina un nodo.

Headers de Contexto (Localización y Zona Horaria)

Header	Descripción	Ejemplo de Valor
Accept-Language	Código ISO 639-1 para traducir el campo title.	es, en
X-Timezone	Zona horaria IANA para formatear el campo created_at.	America/Caracas, UTC








