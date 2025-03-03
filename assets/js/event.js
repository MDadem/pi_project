document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            // 🇹🇳 Tunisie
            { title: 'Festival de Carthage', start: '2025-07-10', end: '2025-07-20', description: 'Tunis, Tunisie' },
            { title: 'Festival International du Sahara', start: '2025-12-20', end: '2025-12-25', description: 'Douz, Tunisie' },
            { title: 'Festival des Ksour', start: '2025-03-15', end: '2025-03-20', description: 'Tataouine, Tunisie' },
            { title: 'Fête de l\'Indépendance (Tunisie)', start: '2025-03-20', description: 'Tunisie' },
            { title: 'Fête de la République (Tunisie)', start: '2025-07-25', description: 'Tunisie' },

            // 🇩🇿 Algérie
            { title: 'Festival du Film d\'Alger', start: '2025-10-10', end: '2025-10-15', description: 'Alger, Algérie' },
            { title: 'Festival Européen d\'Alger', start: '2025-05-15', end: '2025-05-25', description: 'Alger, Algérie' },
            { title: 'Festival Culturel de Timgad', start: '2025-07-20', end: '2025-07-25', description: 'Batna, Algérie' },
            { title: 'Fête de l\'Indépendance (Algérie)', start: '2025-07-05', description: 'Algérie' },
            { title: 'Yennayer (Nouvel An Amazigh)', start: '2025-01-12', description: 'Célébration berbère' },

            // 🇲🇦 Maroc
            { title: 'Festival des Musiques Sacrées de Fès', start: '2025-06-01', end: '2025-06-07', description: 'Fès, Maroc' },
            { title: 'Festival Mawazine', start: '2025-06-20', end: '2025-06-30', description: 'Rabat, Maroc' },
            { title: 'Festival Gnaoua et Musiques du Monde', start: '2025-07-05', end: '2025-07-10', description: 'Essaouira, Maroc' },
            { title: 'Fête du Trône (Maroc)', start: '2025-07-30', description: 'Maroc' },
            { title: 'Fête de l\'Indépendance (Maroc)', start: '2025-11-18', description: 'Maroc' },

            // 🇲🇷 Mauritanie
            { title: 'Festival des Dattes de Tidjikja', start: '2025-11-01', end: '2025-11-07', description: 'Tidjikja, Mauritanie' },
            { title: 'Festival des Villes Anciennes', start: '2025-02-15', end: '2025-02-20', description: 'Chinguetti, Mauritanie' },
            { title: 'Festival International de Nouakchott', start: '2025-03-10', end: '2025-03-15', description: 'Nouakchott, Mauritanie' },
            { title: 'Fête de l\'Indépendance (Mauritanie)', start: '2025-11-28', description: 'Mauritanie' },

            // 🌍 Événements communs
            { title: 'Aïd el-Fitr', start: '2025-03-30', description: 'Fête musulmane célébrée dans plusieurs pays' },
            { title: 'Aïd al-Adha', start: '2025-06-07', description: 'Fête musulmane célébrée dans plusieurs pays' }
        ],
        eventContent: function(arg) {
            return { 
                html: `<b>${arg.event.title}</b><br>${arg.event.extendedProps.description}`
            };
        }
    });
    calendar.render();
});
