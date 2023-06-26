## Calendar Manager API

---
Google Calendar API for comfort using in legacy projects

### Installation
Copy and configure .env:
```sh
cp .env.example .env
```
Clone repo and install dependencies:
```sh
composer install
```
Check read/write rules for dir:  
`storage/app/google-calendar`

### Endpoints
 * `/api/events`
 * `/api/add-event`
 * `/api/get-event`
 * `/api/update-event`
 * `/api/delete-event`
 * `/api/get-client`
 * `/api/json`
