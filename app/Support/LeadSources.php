<?php

namespace App\Support;

class LeadSources
{
    public static function informationOptions(): array
    {
        return [
            'Walk-In' => ['Call center', 'Website', 'Digital Marketing', 'Radio', 'Press', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Direct', 'Hoarding', 'Referral', 'Existing Customer', 'Personal Contact', 'TVC', 'Activity', 'Other'],
            'Tele-In' => ['Call center', 'TVC', 'Website', 'Digital Marketing', 'Radio', 'Press', 'Hoarding', 'Activity', 'Referral', 'Existing Customer', 'Personal Contact', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Other'],
            'Activity' => ['Call center', 'Road Show/Held Event', 'Radio', 'Leaflet', 'Press', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Finance Company', 'Display Company', 'Customer Visit', 'Motor Show', 'Test Drive Campaign', 'Leaflet Campaign', 'Road Show', 'Society Meeting', 'Events', 'Permit Campaign', 'Other'],
            'Digital' => ['Call center', 'Website', 'Facebook', 'YouTube', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Press', 'Radio', 'Instagram', 'Tiktok', 'Riyasewana', 'Email Campaign', 'Whatsapp campaign', 'SMS Campaign', 'Other'],
            'Referral' => ['Call center', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Press', 'Radio', 'Introducer', 'Existing Customer', 'Personal Contact', 'Finance Company', 'Broker', 'Internal Staff', 'Other'],
            'Press' => ['Newspaper', 'Hoarding/Banner', 'TVC', 'Press', 'Radio', 'Facebook Lead Gen', 'IKMAN', 'Whatsapp', 'Other'],
        ];
    }
}
