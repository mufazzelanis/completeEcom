<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageCtaClick extends Model
{
    public const PLACEMENTS = [
        'hero'       => 'Hero "Order Now" button',
        'offer'      => 'Offer/price box button',
        'sticky-bar' => 'Sticky bottom bar',
    ];

    public $timestamps = false;

    protected $fillable = ['landing_page_id', 'landing_page_visit_id', 'placement', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function landingPage() { return $this->belongsTo(LandingPage::class); }

    public function visit() { return $this->belongsTo(LandingPageVisit::class, 'landing_page_visit_id'); }
}
