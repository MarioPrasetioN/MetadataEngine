<img width="1919" height="991" alt="image" src="https://github.com/user-attachments/assets/8ba3e094-ac12-4845-b9f1-89d3c9604e4e" />


# Metadata Engine Backend (Prototype) + Filament Exploration

A **Laravel 8 backend system** for managing radio playout metadata and automated handovers.  
This project demonstrates **complex backend pipelines**, including JSON processing, HTML generation, FTP uploads, and HTTP calls.

The goal was to replace the current code and revamp it with a new system more readable and more scalable/adding new handovers for new radio stations or handover methods

## Features

- **Simple Flow:** Receives JSON payload → performs a simple HTTP call and logs the result.  
- **Mid Flow:** Receives JSON payload → combines with mock/DB data → generates an HTML “now playing” table → uploads to FTP.  
- **Complex Flow:** Receives JSON payload → Runs a pipeline tasks with Laravel Pipeline feature, the tasks are both flows above.
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
