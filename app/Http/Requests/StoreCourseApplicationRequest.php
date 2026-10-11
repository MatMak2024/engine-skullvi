<?php

namespace App\Http\Requests;

use App\Models\Course;

class StoreCourseApplicationRequest extends StoreApplicationRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user() !== null
            && $course instanceof Course
            && $this->user()->can('apply', $course);
    }
}
