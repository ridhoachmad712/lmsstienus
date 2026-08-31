<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $month = $request->query('month');
        try {
            $cursor = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = Carbon::now()->startOfMonth();
        }

        $start = $cursor->copy()->startOfMonth();
        $end = $cursor->copy()->endOfMonth();

        // Hanya kelas aktif — agar pertemuan/deadline kelas yang sudah selesai tidak ikut tampil
        $courseIds = ($user->isDosen() ? $user->teachingCourses() : $user->enrolledCourses())
            ->where('courses.status', Course::STATUS_ACTIVE)
            ->pluck('courses.id');

        $meetings = Meeting::whereIn('course_id', $courseIds)
            ->whereNotNull('date')
            ->whereBetween('date', [$start, $end])
            ->with('course')
            ->get();

        $deadlines = Assignment::whereIn('course_id', $courseIds)
            ->where('published', true)
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end])
            ->with('course')
            ->get();

        // Map per tanggal 'Y-m-d'
        $events = [];
        foreach ($meetings as $m) {
            $events[$m->date->format('Y-m-d')]['meetings'][] = $m;
        }
        foreach ($deadlines as $a) {
            $events[$a->deadline->format('Y-m-d')]['deadlines'][] = $a;
        }
        $agenda = collect();
        foreach ($meetings as $meeting) {
            $agenda->push([
                'at' => $meeting->attend_opens_at ?? $meeting->date->copy()->startOfDay(),
                'has_time' => (bool) $meeting->attend_opens_at,
                'type' => 'meeting',
                'title' => $meeting->course->name,
                'subtitle' => 'Pertemuan '.$meeting->number.' · '.$meeting->topic,
                'url' => route('courses.show', $meeting->course),
            ]);
        }
        foreach ($deadlines as $assignment) {
            $agenda->push([
                'at' => $assignment->deadline,
                'has_time' => true,
                'type' => 'deadline',
                'title' => 'Deadline '.$assignment->title,
                'subtitle' => $assignment->course->name,
                'url' => route('assignments.show', $assignment),
            ]);
        }
        $agendaGroups = $agenda->sortBy('at')->groupBy(fn (array $item) => $item['at']->format('Y-m-d'));

        // Grid: Minggu–Sabtu (Minggu didahulukan)
        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);
        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('calendar.index', [
            'cursor' => $cursor,
            'days' => $days,
            'events' => $events,
            'prevMonth' => $cursor->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $cursor->copy()->addMonth()->format('Y-m'),
            'meetings' => $meetings->sortBy('date'),
            'deadlines' => $deadlines->sortBy('deadline'),
            'agendaGroups' => $agendaGroups,
        ]);
    }
}
