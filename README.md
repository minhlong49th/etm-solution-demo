# ETM SOLUTION
## About setup project
    1. Install Portgess DB
    2. If run in local then install ngrok to get data from webhook
    3. Get and setup data for whatsap in env file
        a. WHATSAPP_ACCESS_TOKEN
            i. can use 24h access token from developer page
            ii. can use lifetime access token from business page
        b. WHATSAPP_FROM_PHONE_NUMBER_ID
            i. can use test phone in developer page
            ii. can use a register phone number after register it in whatsapp businesss
    4. config webhook on facebook developer => https://domain/webhook
### Prerequisite
### Run command following these step:
    1. npm install
    2. composer dump-autoload
    3. php artisan serve
    4. If use ngrok for local: ngrok http --domain=noted-rattler-reasonably.ngrok-free.app 8000
* Configuration
* Dependencies
* Database configuration
* How to run tests
* Deployment instructions
