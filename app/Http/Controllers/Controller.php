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

    /**
     * @param Request $request
     * @return array
     */
    public function read(Request $request): array
    {
        $params = $request->all();

        try {
            $events = Event::get();
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true);
        }
        return $events->toArray();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request): array
    {
        $params = $request->all();
        try {
            // create a new event
            $event = Event::create([
                'name' => $params['name'],
                'description' => $params['description'],
                // 2023-06-26 17:30:00
                'startDateTime' => Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $params['start']),
                'endDateTime' => Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $params['end']),
                //'startDateTime' => Carbon\Carbon::now(),
                //'endDateTime' => Carbon\Carbon::now()->addHour(),
            ]);
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true);
        }

        return [
            'summary' => $event->googleEvent->summary,
            'description' => $event->googleEvent->description,
            'start' => $event->googleEvent->getStart(),
            'end' => $event->googleEvent->getEnd(),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        $params = $request->all();
        $events = Event::get();
        // TODO
        $firstEvent = $events->first();
        $firstEvent->name = 'updated name';

        try {
            $firstEvent->save();

            $firstEvent->update(['name' => 'updated again']);
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true);
        }

        return [
            'summary' => $firstEvent->googleEvent->summary,
            'description' => $firstEvent->googleEvent->description,
            'start' => $firstEvent->googleEvent->getStart(),
            'end' => $firstEvent->googleEvent->getEnd(),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function delete(Request $request): array
    {
        $params = $request->all();

        try {
            $events = Event::get();
            // TODO
            $firstEvent = $events->first();

            $firstEvent->delete();
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true);
        }

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
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setScopes('https://www.googleapis.com/auth/calendar');
        $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $client->setAccessType('offline');
        $client->setRedirectUri('https://dev4.pay2me.com/calendar-manager/public/api/get-client');
        $credentialsPath = storage_path('app/google-calendar/oauth-token.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(
                file_get_contents($credentialsPath),
                true
            );
        } else {
            $authUrl = $client->createAuthUrl();
            if ($request->get('code')) {
                $authCode = $request->get('code');
            } else {
                exit("<a href='$authUrl'>auth</a>");
            }

            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken, JSON_PRETTY_PRINT));
        }
        $client->setAccessToken($accessToken);
        if ($client->isAccessTokenExpired()) {
            $jsonCred = file_get_contents($credentialsPath);
            $jsonArray = json_decode($jsonCred, true);
            if (empty($jsonArray['refresh_token'])) {
                unlink($credentialsPath);
                return ['error' => 'refresh_token needed! Please refresh page'];
            }
            $client->fetchAccessTokenWithRefreshToken(
                $jsonArray['refresh_token']
            );
            $newAccessToken = $client->getAccessToken();
            $accessToken = array_merge($jsonArray, $newAccessToken);
            file_put_contents($credentialsPath, json_encode($accessToken));
        }
        return ['saved' => $credentialsPath];
    }

    public function json(Request $request)
    {
        return response()->json($request->all());
        //2023-06-26 17:30:00
        $date = Carbon::createFromFormat('m/d/Y', $myDate)->format('Y-m-d');
    }
}
