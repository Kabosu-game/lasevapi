<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodComfortForm extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'has_allergies',
        'allergy_food',
        'allergy_reaction',
        'allergy_type',
        'intolerances',
        'specific_diets',
        'happiness_ingredients',
        'disliked_foods',
        'spice_level',
        'culinary_inspirations',
        'comfort_dish',
        'breakfast_preference',
        'hot_drinks',
        'plant_drinks',
        'needs_snacks',
        'free_comments',
        'retreat_plan_id',
    ];

    protected $table = 'food_comfort_forms';

    protected $casts = [
        'allergy_type' => 'array',
        'specific_diets' => 'array',
        'happiness_ingredients' => 'array',
        'culinary_inspirations' => 'array',
        'hot_drinks' => 'array',
        'plant_drinks' => 'array',
        'has_allergies' => 'boolean',
        'needs_snacks' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function retreatPlan()
    {
        return $this->belongsTo(RetreatPlan::class);
    }
}
