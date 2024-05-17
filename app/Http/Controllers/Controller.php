<?php

namespace App\Http\Controllers;

use Exception;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Spatie\GoogleCalendar\Event;
use Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param Request $request
     * @return array
     */
    public function list(Request $request): array
    {
        $params = $request->all();
        try {
            $this->checkOrUpdateToken();

            return Event::get()->toArray();
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true) ?? ['exception' => $exception->getMessage()];
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    public function read(Request $request): array
    {
        $params = $request->all();
        //$date = Carbon::createFromFormat('m/d/Y', $myDate)->format('Y-m-d');

        try {
            $this->checkOrUpdateToken();

            if (!empty($params['start']) && !empty($params['end'])) {
                $start = Carbon\Carbon::parse($params['start']);
                $end = Carbon\Carbon::parse($params['end']);

                $events = Event::get($start, $end);
            } else {
                $events = Event::get();
            }
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true) ?? ['status' => 'Not found'];
        }
        return $events->toArray();
    }

    /**
     * @param $id
     * @return array
     */
    public function find($id): array
    {
        try {
            $this->checkOrUpdateToken();

            return (array)Event::find($id);
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true) ?? ['status' => 'Not found'];
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request): array
    {
        $params = $request->all();
        //$date = Carbon::createFromFormat('m/d/Y', $myDate)->format('Y-m-d');

//        $event = new Event;
//        $event->name = 'A new event (1)';
//        $event->description = 'Event description';
//        $event->startDateTime = Carbon\Carbon::now();
//        $event->endDateTime = Carbon\Carbon::now()->addHour();
//        $event->addAttendee([
//            'email' => 'john@example.com',
//            'name' => 'John Doe',
//            'comment' => 'Lorum ipsum',
//        ]);
//        $event->addAttendee(['email' => 'anotherEmail@gmail.com']);
//        $event->addMeetLink(); // optionally add a google meet link to the event
//
//        $event->save();

        try {
            $this->checkOrUpdateToken();

            // create a new event\
            /** @var $event Event */
            $event = Event::create([
                'name' => $params['name'] ?? '',
                'description' => $params['description'] ?? '',
                // 2023-06-26 17:30:00
                'startDateTime' => Carbon\Carbon::createFromFormat(DATE_ATOM, $params['start']),
                'endDateTime' => Carbon\Carbon::createFromFormat(DATE_ATOM, $params['end']),
                //'startDateTime' => Carbon\Carbon::now(),
                //'endDateTime' => Carbon\Carbon::now()->addHour(),
                'colorId' => $params['colorId'],
            ]);

            // $event = new Event;
            // $event->name = $params['name'] ?? '';
            // $event->description = $params['description'] ?? '';
            // $event->startDateTime = Carbon\Carbon::createFromFormat(DATE_ATOM, $params['start']);
            // $event->endDateTime = Carbon\Carbon::createFromFormat(DATE_ATOM, $params['end']);
            // $event->colorId = $params['colorId'];
            if (!empty($params['attendee']) && $params['attendee'] != '') {
                $event->addAttendee([
                    'email' => $params['attendee'],
                    'name' => 'P2M Booking',
                    'comment' => 'Ваше бронирование',
                ]);
                $optParams = [
                    // 'sendNotifications' => true, // deprecated
                    'sendUpdates' => 'all',
                ];
                $event->save('updateEvent', $optParams);
            }

        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true) ?? ['exception' => $exception->getMessage()];
        }

        // TODO
        //return (array)$event->googleEvent;
        return [
            'id' => $event->googleEvent->id,
            'summary' => $event->googleEvent->summary,
            'description' => $event->googleEvent->description,
            'start' => $event->googleEvent->getStart()->dateTime,
            'end' => $event->googleEvent->getEnd()->dateTime,
            'link' => $event->googleEvent->htmlLink,
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        $params = $request->all();
        //$date = Carbon::createFromFormat('m/d/Y', $myDate)->format('Y-m-d');


        try {
            $this->checkOrUpdateToken();

            $events = Event::get();
            // TODO
            $firstEvent = $events->first();
            $firstEvent->name = 'updated name';
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
//exit(json_encode($params, JSON_PRETTY_PRINT));
        try {
            $this->checkOrUpdateToken();

            $event = Event::find($params['id'] ?? null);

            $event->delete();
        } catch (Exception|BindingResolutionException $exception) {
            return [
                'exception' => json_decode($exception->getMessage(), true),
            ];
        }

        return [
            'summary' => $event->googleEvent->summary,
            'description' => $event->googleEvent->description,
            'start' => $event->googleEvent->getStart(),
            'end' => $event->googleEvent->getEnd(),
        ];
    }

    public function clear(Request $request): array
    {
        $events = Event::get(Carbon\Carbon::now()->subWeek());
        /** @var Event $event */
        foreach ($events as $event) {
            $event->delete();
        }

        return [
            'events' => $events,
        ];
    }

    public function getClient(Request $request)
    {
        try {
            $client = $this->getGoogleClient();
        } catch (Exception $exception) {
            return json_decode($exception->getMessage(), true);
        }
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
            //return [
            //    'result' => 'success',
            //    'saved' => $credentialsPath,
            //    'accessToken' => $accessToken,
            //];
            return redirect('/api/get-client');
        }
        $client->setAccessToken($accessToken);
        if ($client->isAccessTokenExpired()) {
            try {
                return $this->updateToken($credentialsPath, $client);
            } catch (Exception $e) {
                return [
                    'result' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }
        return [
            'result' => 'success',
            'message' => 'token is OK',
        ];
    }

    public function json(Request $request): array
    {
        return $request->all();
        //2023-06-26 17:30:00
        $date = Carbon::createFromFormat('m/d/Y', $myDate)->format('Y-m-d');
    }

    /**
     * @return Google_Client
     * @throws \Google\Exception
     */
    private function getGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Google API');
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $client->setAccessType('offline');
        $client->setRedirectUri(env('APP_URL') . '/api/get-client');
        return $client;
    }

    /**
     * @param string $credentialsPath
     * @param Google_Client $client
     * @return array
     * @throws Exception
     */
    private function updateToken(string $credentialsPath, Google_Client $client): array
    {
        $jsonCred = file_get_contents($credentialsPath);
        $jsonArray = json_decode($jsonCred, true);
        if (empty($jsonArray['refresh_token'])) {
            File::delete($credentialsPath);
            //return [
            //    'result' => 'error',
            //    'message' => 'refresh_token needed! Please refresh page',
            //];
            throw new Exception('token is EXPIRED, refresh this page');
        }
        $client->fetchAccessTokenWithRefreshToken(
            $jsonArray['refresh_token']
        );
        $newAccessToken = $client->getAccessToken();
        $oldAccessToken = json_decode(
            file_get_contents($credentialsPath),
            true
        );
        //exit(json_encode([$oldAccessToken, $newAccessToken], JSON_PRETTY_PRINT));
        if ($newAccessToken == null || $oldAccessToken['access_token'] == $newAccessToken['access_token']) {
            File::delete($credentialsPath);
            throw new Exception('Refresh token is NOT updated, reload get-client page!');
        }
        $accessToken = array_merge($jsonArray, $newAccessToken);
        file_put_contents($credentialsPath, json_encode($accessToken, JSON_PRETTY_PRINT));
        return [
            'result' => 'success',
            'message' => 'token is UPDATED',
            'accessToken' => $accessToken,
        ];
    }

    /**
     * @throws \Google\Exception
     */
    private function checkOrUpdateToken(): void
    {
        $client = $this->getGoogleClient();
        if ($client->isAccessTokenExpired()) {
            $credentialsPath = storage_path('app/google-calendar/oauth-token.json');
            $this->updateToken($credentialsPath, $client);
        }
    }

    /**
     * @throws \Google\Exception
     */
    public function forceUpdateToken(int $count = 1): array
    {
        $client = $this->getGoogleClient();
        $credentialsPath = storage_path('app/google-calendar/oauth-token.json');
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            try {
                $result[$i] = [
                    'index' => $i,
                    'result' => (bool)$this->updateToken($credentialsPath, $client),
                    'raw' => $this->updateToken($credentialsPath, $client),
                ];
            } catch (Exception $e) {
                $result[$i] = [
                    'index' => $i,
                    'result' => false,
                    'error' => $e->getMessage(),
                ];
                return $result;
            }
        }

        return $result;
    }
}
