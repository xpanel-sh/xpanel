<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class HostingPlanController extends Controller
{
    public function index()
    {
        $plans = HostingPlan::query()
            ->withCount('tenants')
            ->latest()
            ->paginate(12);

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create', ['plan' => new HostingPlan()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        HostingPlan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado correctamente.');
    }

    public function edit(HostingPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, HostingPlan $plan)
    {
        $validated = $this->validatePlan($request, $plan);
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado correctamente.');
    }

    public function toggle(HostingPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return redirect()->route('admin.plans.index')->with('success', 'Estado del plan actualizado.');
    }

    private function validatePlan(Request $request, ?HostingPlan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('hosting_plans', 'slug')->ignore($plan)],
            'max_sites' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_databases' => ['required', 'integer', 'min:0', 'max:100000'],
            'storage_mb' => ['required', 'integer', 'min:0', 'max:100000000'],
            'bandwidth_gb' => ['required', 'integer', 'min:0', 'max:1000000'],
            'email_accounts' => ['required', 'integer', 'min:0', 'max:100000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
