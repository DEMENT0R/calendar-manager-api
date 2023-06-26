<?php

namespace App\Http\Controllers;

use DateInterval;
use DateTime;
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

    public function getEvents()
    {
        $events = Event::get();

        return $events->toArray();
    }
    public function addEvent()
    {
        //create a new event
        $event = new Event;

        $event->name = 'A new event';
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

        dd($event);
    }

    public function getCalendarTest()
    {
        //create a new event
        $event = new Event;

        $event->name = 'A new event';
        $event->description = 'Event description';
        //$event->startDateTime = Carbon\Carbon::now();
        //$event->endDateTime = Carbon\Carbon::now()->addHour();
        $event->startDateTime = new DateTime();
        $event->endDateTime = (new DateTime())->add(
            DateInterval::createFromDateString(
                "1 hour"
            )
        );
        //->format('Y-m-d H:i:s')
        $event->addAttendee([
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'comment' => 'Lorum ipsum',
        ]);
        $event->addAttendee(['email' => 'anotherEmail@gmail.com']);
        $event->addMeetLink(); // optionally add a google meet link to the event

        $event->save();

        // get all future events on a calendar
        $events = Event::get();

        // update existing event
        $firstEvent = $events->first();
        $firstEvent->name = 'updated name';
        $firstEvent->save();

        $firstEvent->update(['name' => 'updated again']);

        // create a new event
        Event::create([
            'name' => 'A new event',
            'startDateTime' => Carbon\Carbon::now(),
            'endDateTime' => Carbon\Carbon::now()->addHour(),
        ]);

        // delete an event
        $event->delete();
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
