<?php

namespace App\Http\Controllers;

use App\Models\CampaignRate;
use App\Models\CampaignsCategory;
use App\Models\HomepageFeaturedCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Campaign;
use App\Models\Country;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $category_selected = 0;
        $campaigns = Campaign::incentAndMobile(false)->active();

        $campaigns = Campaign::active();
        if ($request->has('category')) {
            $category_selected = (int) $request->category;
            $campaigns = $campaigns->where('category_id', $category_selected);
        }
        if($request->has('search')) {
            $campaigns = $campaigns->where('name', 'like', '%'.$request->search.'%');
        }
        if($request->has('country')) {
            $code = (int) $request->country;
            if($code) {
                $campaigns = $campaigns
                    ->leftJoin('campaign_countries', 'campaigns.id', '=', 'campaign_countries.campaign_id')
                    ->where('campaign_countries.country_id', '=', $code);
            }
        }

        $campaigns = $campaigns->get();
        $categories = CampaignsCategory::whereIn(
            'id',
            Campaign::active()
                ->incentAndMobile(false)
                ->select('category_id')
                ->get()
                ->pluck('category_id')
                ->toArray()
        )->get();


        $category = new CampaignsCategory();
        $category->name = 'All Categories';
        $category->id = 0;
        $categories = $categories->push($category)
                                ->sortBy('id');

        $countries = Country::all();
        $country = new Country();
        $country->name = 'All Countries';
        $country->id = 0;
        $countries = $countries->push($country)
                                ->sortBy('id')
                                ->pluck('name', 'id');
        return view('user.campaigns.index')->with(compact('campaigns', 'countries', 'categories', 'category_selected'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //$campaign = Campaign::where('id', $id)->incentAndMobile(false)->active()->with('category', 'reports')->firstOrFail();
		$campaign = Campaign::where('id', $id)->active()->with('category', 'reports')->firstOrFail();
        $custom_rate = CampaignRate::where(['active' => 'yes', 'campaign_id' => (int) $id, 'user_id' => (int) auth()->user()->id])->first();
        $cap_daily_status = TrackController::checkDailyCap($campaign);
        $images = null;

        $destinationPath = storage_path() . '/app/images/campaign/' . $campaign->id . '/gallery';
        if( \File::exists($destinationPath) ) {
            $files = collect( scandir($destinationPath) );
            $files->forget([0,1]); // Remove . and ..
            $images = $files;
        }

        return view('user.campaigns.show')->with(array('campaign' => $campaign, 'cap_daily_status' => $cap_daily_status, 'custom_rate' => $custom_rate, 'images' => $images));
    }
}
