# ⚡ Field Service

Portal web de gestión de servicios de campo inspirado en Salesforce Field Service Lightning (FSL), desarrollado como proyecto final del ciclo **DAW (Desarrollo de Aplicaciones Web)** en el CIFP César Manrique, Tenerife.

---

## 📋 Descripción

Field Service es una aplicación web full-stack que permite gestionar órdenes de trabajo, citas de servicio, técnicos, ausencias y reportes en entornos de campo. Está diseñada para equipos de mantenimiento, instalación y reparación que necesitan coordinar trabajo en campo desde una plataforma centralizada.

---

## 🚀 Tecnologías

### Backend
- **PHP 8.4** + **Laravel 13**
- **MySQL** — base de datos relacional
- **Laravel Sanctum** — autenticación por tokens
- **Resend** — envío de emails transaccionales
- **Carbon** — manejo de fechas

### Frontend
- **Vue 3** + **Vite**
- **Pinia** — gestión de estado
- **Vue Router** — enrutamiento
- **Axios** — cliente HTTP
- **FullCalendar** — vista de calendario
- **Chart.js** + **vue-chartjs** — gráficos
- **jsPDF** + **jspdf-autotable** — exportación a PDF
- **Google Maps API** — autocompletado de direcciones y mapas

### Control de versiones
- **GitHub** — repositorio: [proyecto-final-DAW](https://github.com/JuanMarcosL/proyecto-final-DAW)

---

## ✨ Funcionalidades

### Gestión de Órdenes de Trabajo (OT)
- CRUD completo de órdenes de trabajo
- Estados: Abierta, En progreso, Cerrada, Cancelada
- Prioridades: Baja, Media, Alta, Crítica
- Autocompletado de dirección con Google Places
- Mapa de ubicación en vista detalle
- Vista detalle con citas de servicio relacionadas

### Citas de Servicio (SA)
- Creación automática al crear una OT
- Asignación de técnico con validación de disponibilidad
- Bloqueo de citas en periodos de ausencia aprobada
- Notificación por email al técnico al ser asignado
- Estados: Borrador, Programada, En progreso, Completada, Cancelada

### Calendario
- Vista mensual/semanal/diaria con FullCalendar
- Visualización de citas y ausencias aprobadas
- Código de colores por estado
- Modal de detalle al hacer clic en un evento

### Gestión de Técnicos
- Listado con especialidad, zona y estado
- Activar/desactivar técnicos
- Citas activas por técnico

### Ausencias
- Solicitud de ausencia por técnico
- Aprobación/rechazo por supervisor o admin
- Bloqueo automático en calendario y asignación de citas
- Notificación por email a supervisores/admins al solicitar ausencia

### Reportes
- OTs por estado (gráfico de dona)
- OTs por prioridad (gráfico de barras)
- Citas por técnico (gráfico de barras)
- Ausencias por técnico (gráfico de barras)
- Listado completo de OTs exportable a PDF

### Gestión de Usuarios
- Roles: Administrador, Supervisor, Técnico
- Jerarquía de permisos por rol
- Creación con envío de email para establecer contraseña
- Recuperación de contraseña por email
- Restricciones de acceso por rol en frontend y backend

## ⚙️ Instalación local

### Requisitos
- PHP 8.4
- Composer
- Node.js v22+
- MySQL
- Docker (para MySQL en local)

### Backend

cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

### Frontend

cd frontend
npm install
cp .env.example .env
npm run dev

### Variables de entorno necesarias

**Backend `.env`:**
```
DB_DATABASE=field_service
DB_USERNAME=root
DB_PASSWORD=tu_password

MAIL_MAILER=resend
RESEND_API_KEY=tu_api_key
MAIL_FROM_ADDRESS="noreply@tudominio.com"

FRONTEND_URL=http://localhost:5173


**Frontend `.env`:**

VITE_GOOGLE_MAPS_API_KEY=tu_api_key


---

## 👤 Usuario por defecto

```
Email: admin@fieldservice.com
Password: password123
```

---

## 📁 Estructura del proyecto

```
proyecto-final-DAW/
├── backend/                  # Laravel 13
│   ├── app/
│   │   ├── Http/Controllers/ # AuthController, WorkOrderController...
│   │   ├── Models/           # User, WorkOrder, Appointment...
│   │   └── Notifications/    # WelcomeNotification, AppointmentAssigned...
│   ├── database/migrations/
│   └── routes/api.php
├── frontend/                 # Vue 3 + Vite
│   └── src/
│       ├── views/            # LoginView, WorkOrdersView, CalendarView...
│       ├── services/         # api.js, workorders.js, appointments.js...
│       ├── stores/           # auth.js (Pinia)
│       └── assets/css/       # Estilos por vista
└── README.md


---

## 📄 Licencia

Este proyecto está bajo la licencia MIT.

---

## 👨‍💻 Autor

**Juan Marcos León**  
DAW Semipresencial — CIFP César Manrique, Tenerife  