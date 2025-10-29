<!-- BEGIN: MAIN -->
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <h2 class="h4 mb-4">{PHP.L.usrnewnotify_admin_title}</h2>
		{FILE "{PHP.cfg.system_dir}/admin/tpl/warnings.tpl"}
    <!-- Форма фильтров -->
    <form method="get" action="{FILTER_ACTION_URL}" class="row g-3 mb-4 align-items-end">
        <input type="hidden" name="m" value="other">
        <input type="hidden" name="p" value="usrnewnotify">
        <!-- Пользователь -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.User}</label>
            <input type="text" name="filter_username" value="{FILTER_USERNAME}" class="form-control form-control-sm" placeholder="{PHP.L.User}">
        </div>
        <!-- Email -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.Email}</label>
            <input type="email" name="filter_email" value="{FILTER_EMAIL}" class="form-control form-control-sm" placeholder="{PHP.L.Email}">
        </div>
        <!-- IP -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.IP}</label>
            <input type="text" name="filter_ip" value="{FILTER_IP}" class="form-control form-control-sm" placeholder="192.168...">
        </div>
        <!-- Страна -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.Country}</label>
            <input type="text" name="filter_country" value="{FILTER_COUNTRY}" class="form-control form-control-sm" placeholder="RU">
        </div>
        <!-- Статус -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.Status}</label>
            <select name="filter_status" class="form-select form-select-sm">
                <option value="">{PHP.L.All}</option>
                <option value="success" <!-- IF {FILTER_STATUS} == "success" -->selected<!-- ENDIF -->>{PHP.L.Success}</option>
                <option value="error" <!-- IF {FILTER_STATUS} == "error" -->selected<!-- ENDIF -->>{PHP.L.Error}</option>
            </select>
        </div>
        <!-- Дата С -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.DateFrom}</label>
            <input type="date" name="filter_date_from" value="{FILTER_DATE_FROM}" class="form-control form-control-sm">
        </div>
        <!-- Дата ПО -->
        <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
            <label class="form-label">{PHP.L.DateTo}</label>
            <input type="date" name="filter_date_to" value="{FILTER_DATE_TO}" class="form-control form-control-sm">
        </div>
        <!-- Кнопки -->
        <div class="col-sm-12 col-lg-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">{PHP.L.Filter}</button>
            <a href="{PHP|cot_url('admin', 'm=other&p=usrnewnotify')}" class="btn btn-outline-secondary btn-sm w-100">{PHP.L.All}</a>
        </div>
    </form>
    <!-- Таблица логов -->
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th class="text-nowrap">{PHP.L.ID}</th>
                    <th>{PHP.L.User}</th>
                    <th>{PHP.L.Email}</th>
                    <th>{PHP.L.IP}</th>
                    <th>{PHP.L.Country}</th>
                    <th>{PHP.L.Device}</th>
                    <th>{PHP.L.Browser}</th>
                    <th>{PHP.L.Date}</th>
                    <th>{PHP.L.Status}</th>
                    <th>{PHP.L.Message}</th>
                    <th>{PHP.L.user_profile_link}</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <!-- BEGIN: LOGS_ROW -->
                <tr>
                    <td class="text-muted small">{LOG_ID}</td>
                    <td>
                        <a href="{LOG_PROFILE_URL}" class="text-decoration-none">{LOG_USER_NAME}</a>
                    </td>
                    <td class="small text-break">{LOG_USER_EMAIL}</td>
                    <td class="font-monospace small">{LOG_IP}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark">{LOG_COUNTRY}</span>
                    </td>
                    <td class="small">{LOG_DEVICE}</td>
                    <td class="small">{LOG_BROWSER}</td>
                    <td class="small text-nowrap">{LOG_DATE}</td>
                    <td>
                        <!-- IF {LOG_STATUS} == {PHP.L.Success} -->
                        <span class="badge bg-success">{LOG_STATUS}</span>
                        <!-- ELSE -->
                        <span class="badge bg-danger">{LOG_STATUS}</span>
                        <!-- ENDIF -->
                    </td>
                    <td class="small text-break" style="max-width:180px;">{LOG_MESSAGE}</td>
                    <td>
                        <a href="{LOG_PROFILE_URL}" class="btn btn-outline-primary btn-sm" title="{PHP.L.user_profile_link}">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                <!-- END: LOGS_ROW -->
                <!-- BEGIN: NO_LOGS -->
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        {PHP.L.No_logs_found}
                    </td>
                </tr>
                <!-- END: NO_LOGS -->
            </tbody>
        </table>
    </div>
	

    <div class="paging">
        {PREVIOUS_PAGE}{PAGINATION}{NEXT_PAGE}
        <span>{PHP.L.Total}: {TOTAL_ENTRIES}, {PHP.L.Onpage}: {ENTRIES_ON_CURRENT_PAGE}</span>
    </div>

</div>
<!-- END: MAIN -->