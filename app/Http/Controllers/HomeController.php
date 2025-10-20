<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;


class HomeController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function index()
    {
        $users = User::where('status', 'A')->get();

        $mountains = Mountain::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('mountain_name')
            ->get();

       $responses = Http::pool(function (Pool $pool) use ($mountains) {
            foreach ($mountains as $m) {
                // Only make the request if cache is missing
                if (!Cache::has("mountain_weather_{$m->id}")) {
                    $pool->as((string)$m->id)->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude'  => $m->latitude,
                        'longitude' => $m->longitude,
                        'current'   => 'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,wind_speed_10m,wind_direction_10m',
                        'timezone'  => 'Europe/Athens',
                    ]);
                }
            }
        });

        // Build weather data
        $weatherData = [];
        foreach ($mountains as $m) {
            $cacheKey = "mountain_weather_{$m->id}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $weatherData[$m->id] = $cached;
            } else {
                $resp = $responses[(string)$m->id] ?? null;
                $data = ($resp && $resp->ok()) ? ($resp->json()['current'] ?? null) : null;

                $weatherData[$m->id] = $data;

                // Cache for 10 minutes
                if ($data) {
                    Cache::put($cacheKey, $data, now()->addMinutes(10));
                }
            }
        }


        return view('home', [
            'users'       => $users,
            'mountains'   => $mountains,
            'weatherData' => $weatherData,
        ]);
    }

    public function getMountainForecast($id)
    {
        $cacheKey = "mountain_forecast_{$id}";
        $data = Cache::get($cacheKey);

        if (!$data) {
            $mountain = \App\Models\Mountain::findOrFail($id);

            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude'  => $mountain->latitude,
                'longitude' => $mountain->longitude,
                'daily'     => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
                'timezone'  => 'Europe/Athens',
            ]);

            if ($response->ok()) {
                $data = $response->json()['daily'] ?? [];
                Cache::put($cacheKey, $data, now()->addMinutes(60)); // cache for 1 hour
            } else {
                return response()->json(['success' => false], 500);
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
