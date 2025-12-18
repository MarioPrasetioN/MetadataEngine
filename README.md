# Metadata Engine Backend (Prototype)

A **Laravel 8 backend system** for managing radio playout metadata and automated handovers.  
This project demonstrates **complex backend pipelines**, including JSON processing, HTML generation, FTP uploads, and HTTP calls.

The goal was to replace the current code and revamp it with a new system more readable and more scalable/adding new handovers for new radio stations or handover methods

## Features

- **Simple Flow:** Receives JSON payload → performs a simple HTTP call and logs the result.  
- **Mid Flow:** Receives JSON payload → combines with mock/DB data → generates an HTML “now playing” table → uploads to FTP.  
- **Complex Flow:** Receives JSON payload → retrieves media files → uploads files to FTP → triggers multiple HTTP endpoints.  
- **Database Logging:** Stores payloads in `NowPlay` table and logs handover metadata in `MetadataLogging`.  
- **Mock Data Support:** Allows testing with mock JSON datasets for mid and complex flows.  

## Tech Stack

- PHP 8.2  
- Laravel 8  
- MySQL (demo database, migrations included)  
- Laravel Storage (FTP integration)  
- Composer for dependency management  

## Database Structure

### `playout_now_play`
Stores incoming playout data:

| Column            | Type       | Notes                                  |
|------------------|-----------|---------------------------------------|
| playout_id        | string    | ID from the payload                    |
| artist            | string    | Song or audio artist                   |
| title             | string    | Song or audio title                    |
| category          | string    | Playout category                        |
| filename          | string    | Audio filename                          |
| duration          | integer   | Duration in milliseconds                |
| start_time        | datetime  | Actual start time                       |
| planned_start_time| datetime  | Scheduled start time                    |
| cutout            | integer   | Cutout timestamp                        |
| cutout_origin     | integer   | Original cutout value                   |
| mix_point_pr_ev   | integer   | Mix point pre-event                     |
| mix_point_pr_ev_origin | integer | Original mix point                     |
| inserted_element  | boolean   | Whether inserted into playout           |
| drift_ms          | integer   | Drift in milliseconds                   |
| local_code        | string    | Extracted from playout_id               |
| network_code      | string    | Extracted from playout_id               |
| playout_type      | string    | Type of playout (main/backup)          |
| created_at        | timestamp | Automatically set on insert             |

### `metadata_logging`
Logs each handover or processing task:

| Column           | Type       | Notes                                  |
|-----------------|-----------|---------------------------------------|
| metadata_name     | string    | Name of the task                       |
| endpoint          | string    | URL or service endpoint called         |
| response_message  | text      | Response message or error              |
| response_code     | integer   | HTTP or process code                    |
| notes             | text      | Additional notes                        |
| created_at        | timestamp | Automatically set on insert             |

## How to Run

1. Clone the repository:
```bash
git clone https://github.com/marioprasetion/metadata-engine.git
cd metadata-engine

2. setup the env for database and ftp servers
3. install with composer:
```bash
composer install

4. run migrations
```bash
php artisan migrate

5. run the server
```bash
php artisan serve

6. run with postman/browser, change the playout id accordingly:
```bash
http://127.0.0.1:8000/api/handover/nowplay?data=[%20{%20%22playout_id%22:%20%22RC2-B1-RCD-C-someaudio01%22,%20%22artist%22:%20%22-[R2G]%22,%20%22title%22:%20%22-[R2G%20-%20SHOWOPENER%20BU%20NV]%22,%20%22category%22:%20%22J%22,%20%22filename%22:%20%22r2g_Opener_Business_Update_NV.m4a%22,%20%22duration%22:%2012492,%20%22start_time%22:%20%2210.08.2022%2000:06:12.206%22,%20%22planned_start_time%22:%20%2210.08.2022%2000:03:00.000%22,%20%22cutout%22:%2014025,%20%22inserted_element%22:%20false,%20%22drift_ms%22:%200,%20%22playlist_date%22:%20%2209.08.2022%2023:52:36%22,%20%22retryCount%22:%200%20}%20]

