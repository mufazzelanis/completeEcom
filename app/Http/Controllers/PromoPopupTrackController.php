<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PromoPopup;
use Illuminate\Http\Request;

/** Storefront popup results: shown / closed / clicked. A click is remembered for order credit. */
class PromoPopupTrackController extends Controller
{
    public function __invoke(Request $request, PromoPopup $promoPopup)
    {
        $event = $request->input('e');
        $column = ['view' => 'views', 'click' => 'clicks', 'close' => 'closes'][$event] ?? null;

        if ($column && !ActivityLog::isBotUserAgent((string) $request->userAgent())) {
            // query-builder increment: no model events, so the storefront cache isn't reset per view
            PromoPopup::whereKey($promoPopup->id)->increment($column);
            if ($event === 'click') {
                session([PromoPopup::CLICK_SESSION_KEY => ['id' => $promoPopup->id, 'at' => time()]]);
            }
        }

        return response()->noContent();
    }
}
