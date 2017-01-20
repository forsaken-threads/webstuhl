<{{ '?' }}php

namespace App\Resources{{ $group }};

use App\Traits\Weavable;
use Illuminate\Database\Eloquent\Model;

class {{ $name }} extends Model
{
    use Weavable;

    /**
     * Get the contextual validation rules for the Webstuhl resource
     */
    public function getValidationRules()
    {
        return [
            'default' => [],
        ];
    }
}