<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Dipanggil lewat AJAX (polling) untuk memperbarui lonceng notifikasi
     * tanpa perlu reload halaman.
     */
    public function cek(Request $request)
    {
        if (!session()->has('id_user')) {
            return response()->json(['unread' => 0, 'items' => []]);
        }

        $idUser = session('id_user');

        $unread = Notification::where('id_user', $idUser)->where('is_read', false)->count();

        $items = Notification::where('id_user', $idUser)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'type'       => $n->type,
                    'title'      => $n->title,
                    'message'    => $n->message,
                    'url'        => $n->url ?: route('notifikasi.buka', $n->id),
                    'is_read'    => (bool) $n->is_read,
                    'waktu'      => \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans(),
                ];
            });

        return response()->json(['unread' => $unread, 'items' => $items]);
    }

    /**
     * Klik satu notifikasi: tandai sudah dibaca lalu arahkan ke halaman terkait.
     */
    public function buka($id)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $notif = Notification::where('id_user', session('id_user'))->where('id', $id)->first();
        if (!$notif) {
            return redirect()->back();
        }

        if (!$notif->is_read) {
            $notif->update(['is_read' => true]);
        }

        return $notif->url ? redirect($notif->url) : redirect()->back();
    }

    /**
     * Tandai semua notifikasi milik user yang login sebagai sudah dibaca.
     */
    public function bacaSemua(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        Notification::where('id_user', session('id_user'))->where('is_read', false)->update(['is_read' => true]);

        return redirect()->back();
    }
}
