# 📧 Auto PDF Mailer (Safety Pills)

Script en PHP diseñado para automatizar el envío secuencial de boletines o "Tips de Seguridad" en formato PDF. Ideal para ejecutarse mediante Cron Jobs o Tareas Programadas.

## ✨ Características
*   **Envío Secuencial Cíclico:** Recorre alfabéticamente una carpeta de PDFs. Al llegar al último archivo, reinicia el ciclo automáticamente.
*   **Sin Base de Datos:** Utiliza un archivo de estado ligero (`puntero.txt`) para recordar su progreso.
*   **Plantillas Dinámicas:** Inyecta automáticamente el nombre del PDF en el asunto y cuerpo HTML del correo reemplazando la etiqueta `[TEMA]`.
*   **Estructura MIME Nativa:** Envía correos de forma robusta combinando cuerpos en HTML y archivos binarios codificados en Base64.

## 📂 Estructura del Proyecto
```text
auto-pdf-mailer/
├── assets/
│   └── pdfs/               # Coloca aquí tus archivos .pdf de prueba
├── src/
│   └── safetypills.php     # Script principal a ejecutar
├── templates/
│   └── safetymail.html     # Plantilla base del correo
└── README.md