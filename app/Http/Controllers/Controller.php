<?php

namespace App\Http\Controllers;

use Exception;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\GoogleCalendar\Event;
use Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function read(): array
    {
        $events = Event::get();

        return $events->toArray();
    }

    /**
     * @return array
     */
    public function create(): array
    {
        $event = new Event;

        $event->name = 'A new event (1)';
        $event->description = 'Event description';
        $event->startDateTime = Carbon\Carbon::now();
        $event->endDateTime = Carbon\Carbon::now()->addHour();
        $event->addAttendee([
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'comment' => 'Lorum ipsum',
        ]);
        $event->addAttendee(['email' => 'anotherEmail@gmail.com']);
        $event->addMeetLink(); // optionally add a google meet link to the event

        $event->save();

        // create a new event
        //$event = Event::create([
        //    'name' => 'A new event (2)',
        //    'startDateTime' => Carbon\Carbon::now(),
        //    'endDateTime' => Carbon\Carbon::now()->addHour(),
        //]);

        return [
            'summary' => $event->googleEvent->summary,
            'description' => $event->googleEvent->description,
            'start' => $event->googleEvent->getStart(),
            'end' => $event->googleEvent->getEnd(),
        ];
    }

    /**
     * @return array
     */
    public function update(): array
    {
        $events = Event::get();
        // TODO
        $firstEvent = $events->first();
        $firstEvent->name = 'updated name';
        $firstEvent->save();

        $firstEvent->update(['name' => 'updated again']);

        return [
            'summary' => $firstEvent->googleEvent->summary,
            'description' => $firstEvent->googleEvent->description,
            'start' => $firstEvent->googleEvent->getStart(),
            'end' => $firstEvent->googleEvent->getEnd(),
        ];
    }

    /**
     * @return array
     */
    public function delete(): array
    {
        $events = Event::get();
        // TODO
        $firstEvent = $events->first();
        $firstEvent->delete();

        return [
            'summary' => $firstEvent->googleEvent->summary,
            'description' => $firstEvent->googleEvent->description,
            'start' => $firstEvent->googleEvent->getStart(),
            'end' => $firstEvent->googleEvent->getEnd(),
        ];
    }

    public function getClient(Request $request)
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        //$client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setScopes('https://www.googleapis.com/auth/calendar');
        $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $client->setAccessType('offline');
        $client->setRedirectUri('https://dev4.pay2me.com/calendar-manager/public/api/get-client');
        $credentialsPath = storage_path('app/google-calendar/token.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(
                file_get_contents($credentialsPath),
                true
            );
        } else {
            //define('STDIN',fopen("php://stdin","r"));
            $authUrl = $client->createAuthUrl();
            //printf("Open the following link in your browser:\n%s\n", $authUrl);
            //print 'Enter verification code: ';
            //$authCode = trim(fgets(STDIN));
            if ($request->get('code')) {
                $authCode = $request->get('code');
            } else {
                exit("<a href='$authUrl'>auth</a>");
            }
            //$scope = 'https://www.googleapis.com/auth/spreadsheets';

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            exit(json_encode($accessToken, JSON_PRETTY_PRINT));
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);
        if ($client->isAccessTokenExpired()) {
            $jsonCred = file_get_contents($credentialsPath);
            $jsonArray = json_decode($jsonCred, true);
            $client->fetchAccessTokenWithRefreshToken(
                $jsonArray["refresh_token"]
            );
            $newAccessToken = $client->getAccessToken();
            $accessToken = array_merge($jsonArray, $newAccessToken);
            file_put_contents($credentialsPath, json_encode($accessToken));
        }
        return $client;
    }
}
