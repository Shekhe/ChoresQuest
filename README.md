# Chores Quest App

Chores Quest is a family-friendly web application designed to help parents manage children's chores with tasks, points, and rewards. It has two main parts: a parent dashboard and a kids zone.

## Features

- **Parent Dashboard**
  - Add, edit, delete tasks
  - Assign tasks to children
  - Track active, overdue, and completed tasks
  - Manage children (profiles, pictures)
  - Manage rewards (title, points required, images)
  - Settings: dashboard PIN, overdue notifications, auto-delete, date/time sync

- **Kids Zone**
  - Children view their tasks
  - Completed tasks are locked (cannot be undone)
  - Overdue tasks are highlighted in red
  - Visual progress bar to track achievements

## Tech Stack

- **Frontend:** PHP, HTML, CSS, JavaScript
- **Backend:** PHP APIs (`backend_chores_quest/`)
- **Database:** MySQL (schema in `dbdump.sql`)
- **PWA Support:** Service Worker (`sw.js`), `manifest.json`

## Project Structure
```
choresappv1.6/
├── index.php             # Main entry
├── style.css             # Styles
├── manifest.json         # PWA manifest
├── sw.js                 # Service worker
├── dbdump.sql            # Database schema
├── admin/                # Admin dashboard
├── backend_chores_quest/ # Backend APIs
```

## Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/YOUR-USERNAME/chores-quest.git
   ```
2. Import `dbdump.sql` into MySQL.
3. Update database credentials in `backend_chores_quest/config.php`.
4. Run on a local server (e.g., XAMPP, MAMP) or deploy to a PHP server.

## Usage
- Parents log in via `/admin`.
- Kids access tasks via the main app.
- Rewards and progress update dynamically as tasks are completed.

## License
This project is for educational and personal use.  

