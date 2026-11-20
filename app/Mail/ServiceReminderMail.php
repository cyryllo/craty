<?php

namespace App\Mail;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\Repair;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Cotygodniowe przypomnienie z modułu Serwis (SendServiceReminders). */
class ServiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Item>  $inspections
     * @param  Collection<int, Item>  $warranties
     * @param  Collection<int, Repair>  $repairs
     */
    public function __construct(public Collection $inspections, public Collection $warranties, public Collection $repairs)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(':name: service reminder (:count)', [
                'name' => AppSetting::current()->effectiveName(),
                'count' => $this->inspections->count() + $this->warranties->count() + $this->repairs->count(),
            ]),
        );
    }

    public function content(): Content
    {
        $link = fn (Item $item) => '<a href="'.e(route('items.show', $item)).'">'.e($item->name).'</a>';

        $sections = [
            __('Inspections due') => $this->inspections->map(fn (Item $item) => $link($item).': '
                .($item->inspectionState() === 'overdue' ? e(__('overdue since')) : e(__('due'))).' '.$item->next_inspection_at->format('d.m.Y')),
            __('Warranties ending') => $this->warranties->map(fn (Item $item) => $link($item).': '
                .e(__('until')).' '.$item->warranty_until->format('d.m.Y')),
            __('Repairs past their expected return') => $this->repairs->map(fn (Repair $repair) => $link($repair->item).': '
                .e($repair->serviceLabel()).', '.e(__('expected')).' '.$repair->expected_at->format('d.m.Y')),
        ];

        $html = '';
        foreach ($sections as $title => $rows) {
            if ($rows->isNotEmpty()) {
                $html .= '<h3>'.e($title).'</h3><ul><li>'.$rows->implode('</li><li>').'</li></ul>';
            }
        }

        return new Content(htmlString: $html.'<p><a href="'.e(route('service.index')).'">'.e(__('Open the Service page')).'</a></p>');
    }
}
