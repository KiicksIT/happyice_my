<div ng-controller="custOutstandingController">
<div class="col-md-12 col-xs-12">
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('cust_id', 'ID', ['class'=>'control-label search-title']) !!}
                {!! Form::text('cust_id', null,
                                            [
                                                'class'=>'form-control input-sm',
                                                'ng-model'=>'search.cust_id',
                                                'placeholder'=>'Cust ID',
                                                'ng-change'=>'searchDB()',
                                                'ng-model-options'=>'{ debounce: 500 }'
                                            ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('current_month', 'Delivery Month', ['class'=>'control-label search-title']) !!}
                <select class="select form-control" name="current_month" ng-model="search.current_month" ng-change="searchDB()">
                    <option value="">All</option>
                    @foreach($month_options as $key => $value)
                        <option value="{{$key}}" selected="{{Carbon\Carbon::today()->month.'-'.Carbon\Carbon::today()->year ? 'selected' : ''}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('profile_id', 'Profile', ['class'=>'control-label search-title']) !!}
                {!! Form::select('profile_id', [''=>'All']+
                    $profiles::filterUserProfile()
                        ->pluck('name', 'id')
                        ->all(),
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.profile_id',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('company', 'ID Name', ['class'=>'control-label search-title']) !!}
                {!! Form::text('company', null,
                                                [
                                                    'class'=>'form-control input-sm',
                                                    'ng-model'=>'search.company',
                                                    'placeholder'=>'ID Name',
                                                    'ng-change'=>'searchDB()',
                                                    'ng-model-options'=>'{ debounce: 500 }'
                                                ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('status', 'Status', ['class'=>'control-label search-title']) !!}
                {!! Form::select('status', [''=>'All', 'Delivered'=>'Delivered', 'Confirmed'=>'Confirmed', 'Cancelled'=>'Cancelled'], null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.status',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('person_id', 'Customer', ['class'=>'control-label search-title']) !!}
                {!! Form::select('person_id',
                    [''=>'All'] +
                    $customers::select(DB::raw("CONCAT(cust_id,' - ',company) AS full, id"))
                        ->whereActive('Yes')
                        ->where('cust_id', 'NOT LIKE', 'H%')
                        ->whereHas('profile', function($q) {
                            $q->filterUserProfile();
                        })
                        ->orderBy('cust_id')
                        ->pluck('full', 'id')
                        ->all(),
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.person_id',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('payment', 'Payment', ['class'=>'control-label search-title']) !!}
                {!! Form::select('payment',
                    [''=>'All', 'Paid'=>'Paid', 'Owe'=>'Owe'],
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.payment',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('custcategory', 'Cust Category', ['class'=>'control-label search-title']) !!}
                <select name="custcategory" class="selectmultiple form-control" ng-model="search.custcategory" ng-change="searchDB()" multiple>
                    <option value="">All</option>
                    @foreach($custcategories::orderBy('name')->get() as $custcategory)
                    <option value="{{$custcategory->id}}">{{$custcategory->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>           
    </div>
</div>

<div class="row" style="padding-left: 15px;">
    <div class="col-md-4 col-xs-12" style="padding-top: 20px;">
        <button class="btn btn-primary" ng-click="exportData()"><i class="fa fa-file-excel-o"></i><span class="hidden-xs"></span> Export Excel</button>
        <span ng-show="spinner"> <i style="color:red;" class="fa fa-spinner fa-2x fa-spin"></i></span>
    </div>
    <div class="col-md-4 col-xs-12" style="padding-top: 20px;">
            <div class="col-md-5 col-xs-5">
                This Month Total:
            </div>
            <div class="col-md-7 col-xs-7 text-right" style="border: thin black solid">
                <strong>@{{ total_amount | currency: "": 2}}</strong>
            </div>
    </div>
    <div class="col-md-4 col-xs-12 text-right">
        <label for="display_num">Display</label>
        <select ng-model="itemsPerPage" ng-init="itemsPerPage='100'" ng-change="pageNumChanged()">
            <option ng-value="100">100</option>
            <option ng-value="200">200</option>
            <option ng-value="All">All</option>
        </select>
        <label for="display_num2" style="padding-right: 20px">per Page</label>
        <label class="" style="padding-right:18px;" for="totalnum">Showing @{{alldata.length}} of @{{totalCount}} entries</label>
    </div>
</div>

    <div class="table-responsive" id="exportable_custoutstanding" style="padding-top: 20px;">
        <table class="table table-list-search table-hover table-bordered">

            {{-- hidden table for excel export --}}
            <tr class="hidden">
                <td></td>
                <td data-tableexport-display="always">Total Outstanding</td>
                <td data-tableexport-display="always" class="text-right">@{{total_amount | currency: "": 2}}</td>
            </tr>
            <tr class="hidden" data-tableexport-display="always">
                <td></td>
            </tr>

            <tr style="background-color: #DDFDF8">
                <th class="col-md-1 text-center">
                    #
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('cust_id')">
                    ID
                    <span ng-if="search.sortName == 'cust_id' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'cust_id' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('company')">
                    ID Name
                    <span ng-if="search.sortName == 'company' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'company' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('custcategories.id')">
                    Category
                    <span ng-if="search.sortName == 'custcategories.id' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'custcategories.id' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('thistotal')">
                    Outstanding (This Month)
                    <span ng-if="search.sortName == 'thistotal' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'thistotal' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prevtotal')">
                    Outstanding (Last Month)
                    <span ng-if="search.sortName == 'prevtotal' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prevtotal' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prev2total')">
                    Outstanding (Last 2 Months)
                    <span ng-if="search.sortName == 'prev2total' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prev2total' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prevmore3total')">
                    Outstanding (>3 Months)
                    <span ng-if="search.sortName == 'prevmore3total' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prevmore3total' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
            </tr>

            <tbody>
                <tr dir-paginate="transaction in alldata | itemsPerPage:itemsPerPage | orderBy:sortType:sortReverse" pagination-id="cust_outstanding" total-items="totalCount" current-page="currentPage">
                    <td class="col-md-1 text-center">@{{ $index + indexFrom }} </td>
                    <td class="col-md-1 text-left">@{{ transaction.cust_id }} </td>

                    <td class="col-md-1 text-center">
                        <a href="/person/@{{ transaction.person_id }}">
                            @{{ transaction.cust_id[0] == 'D' || transaction.cust_id[0] == 'H' ? transaction.name : transaction.company }}
                        </a>
                    </td>
                    <td class="col-md-1 text-center">@{{ transaction.custcategory }}</td>
                    <td class="col-md-1 text-right">@{{transaction.thistotal | currency: "": 2}}</td>
                    <td class="col-md-1 text-right">@{{transaction.prevtotal | currency: "": 2}}</td>
                    <td class="col-md-1 text-right">@{{transaction.prev2total | currency: "": 2}}</td>
                    <td class="col-md-1 text-right">@{{transaction.prevmore3total | currency: "": 2}}</td>
                </tr>

                <tr ng-if="!alldata || alldata.length == 0">
                    <td colspan="14" class="text-center">No Records Found</td>
                </tr>

            </tbody>
        </table>

        <div>
              <dir-pagination-controls max-size="5" pagination-id="cust_outstanding" direction-links="true" boundary-links="true" class="pull-left" on-page-change="pageChanged(newPageNumber)"> </dir-pagination-controls>
        </div>
    </div>
</div>