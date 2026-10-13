<?php
session_start();

// Generate CSRF token (one per session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تولید CSR - سامانه مؤدیان</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .select-wrapper {
            position: relative;
        }
        .select-wrapper input[type="text"] {
            cursor: pointer;
        }
        .select-wrapper .dropdown-list {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 0 0 8px 8px;
            border-top: none;
            max-height: 220px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .select-wrapper .dropdown-list.active {
            display: block;
        }
        .select-wrapper .dropdown-list .dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background .2s;
            border-bottom: 1px solid #f5f5f5;
        }
        .select-wrapper .dropdown-list .dropdown-item:last-child {
            border-bottom: none;
        }
        .select-wrapper .dropdown-list .dropdown-item:hover {
            background: rgba(109,192,81,0.08);
            color: #6dc051;
        }
        .select-wrapper .dropdown-list .dropdown-item.selected {
            background: rgba(109,192,81,0.15);
            color: #5aa842;
            font-weight: 600;
        }
        .field-error {
            color: #d32f2f;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            direction: rtl;
        }
        .field-error.visible {
            display: block;
        }
        .form-group input.invalid,
        .form-group .select-wrapper input.invalid {
            border-color: #d32f2f;
        }

        /* Conditional fields for Real/Legal person */
        .conditional-field {
            display: none;
        }

        .conditional-field.visible {
            display: block;
        }

        .entity-selector {
            display: flex;
            gap: 12px;
        }

        .entity-selector label.entity-option {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            margin-bottom: 0;
            background: #fff;
        }

        .entity-selector input[type="radio"] {
            width: auto;
            accent-color: #6dc051;
        }

        .entity-selector label.entity-option:hover {
            border-color: #6dc051;
        }

        .entity-selector label.entity-option.selected {
            border-color: #6dc051;
            background: rgba(109,192,81,0.08);
            color: #5aa842;
        }
        .form-group input.valid,
        .form-group .select-wrapper input.valid {
            border-color: #6dc051;
        }
        .badge {
            display: inline-block;
            background: rgba(109,192,81,0.1);
            color: #5aa842;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-right: 6px;
            font-weight: 500;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #6dc051;
            padding-bottom: 8px;
            margin-bottom: 18px;
            margin-top: 28px;
        }
        .section-title:first-child {
            margin-top: 0;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>تولید گواهی CSR</h1>
        <p>تولید کلید خصوصی، کلید عمومی و فایل CSR برای سامانه مودیان</p>
    </div>

    <form action="generate.php" method="post" id="csrForm" novalidate>

        <div class="section-title">اطلاعات مکانی</div>

        <div class="form-row">
            <div class="form-group">
                <label>استان <span class="badge">اجباری</span></label>
                <div class="select-wrapper" id="provinceWrapper">
                    <input
                        type="text"
                        id="provinceInput"
                        placeholder="جستجوی استان..."
                        autocomplete="off"
                        readonly
                        required
                        style="cursor:pointer"
                    >
                    <input type="hidden" name="province" id="provinceValue">
                    <div class="dropdown-list" id="provinceDropdown"></div>
                </div>
                <div class="field-error" id="provinceError">استان را انتخاب کنید</div>
            </div>

            <div class="form-group">
                <label>شهر <span class="badge">اجباری</span></label>
                <div class="select-wrapper" id="cityWrapper">
                    <input
                        type="text"
                        id="cityInput"
                        placeholder="ابتدا استان را انتخاب کنید"
                        autocomplete="off"
                        readonly
                        required
                        style="cursor:pointer"
                    >
                    <input type="hidden" name="city" id="cityValue">
                    <div class="dropdown-list" id="cityDropdown"></div>
                </div>
                <div class="field-error" id="cityError">شهر را انتخاب کنید</div>
            </div>
        </div>

        <div class="section-title">اطلاعات هویتی</div>

        <div class="form-group">
            <label>نوع شخص <span class="badge">اجباری</span></label>
            <div class="entity-selector" id="entitySelector">
                <label class="entity-option selected">
                    <input type="radio" name="person_type" value="real" checked>
                    شخص حقیقی
                </label>
                <label class="entity-option">
                    <input type="radio" name="person_type" value="legal">
                    شخص حقوقی
                </label>
            </div>
        </div>

        <div class="form-group conditional-field" id="entityTypeGroup">
            <label>نوع شخصیت <span class="badge">اجباری</span></label>
            <select name="entity_type" required>
                <option value="">انتخاب کنید</option>
                <option value="دولتی">دولتی</option>
                <option value="غیردولتی">غیردولتی</option>
            </select>
        </div>

        <div class="form-group conditional-field" id="nationalIdGroup">
            <label>شناسه ملی <span class="hint">(۱۱ رقمی - مخصوص اشخاص حقوقی)</span></label>
            <input
                type="text"
                name="national_id"
                id="nationalId"
                maxlength="11"
                placeholder="103200XXXXX"
                autocomplete="off"
                dir="ltr"
                inputmode="numeric"
            >
            <div class="field-error" id="nationalIdError">شناسه ملی باید دقیقاً ۱۱ رقم باشد</div>
        </div>

        <div class="form-group" id="nationalCodeGroup">
            <label id="nationalCodeLabel">کد ملی <span class="hint">(۱۰ رقمی)</span></label>
            <input
                type="text"
                name="national_code"
                id="nationalCode"
                maxlength="10"
                placeholder="0012345678"
                autocomplete="off"
                dir="ltr"
                inputmode="numeric"
            >
            <div class="field-error" id="nationalCodeError">کد ملی باید دقیقاً ۱۰ رقم باشد</div>
        </div>

        <div class="form-group">
            <label>
                نام عمومی (Common Name)
                <span class="hint">فقط حروف لاتین، اعداد و نقطه/خط زیر، بدون فاصله</span>
            </label>
            <input
                type="text"
                name="common_name"
                id="commonName"
                required
                placeholder="ExampleCompany"
                autocomplete="off"
                dir="ltr"
            >
            <div class="field-error" id="commonNameError">فقط حروف انگلیسی، اعداد، نقطه و خط زیر مجاز است (بدون فاصله)</div>
        </div>

        <div class="form-group conditional-field" id="orgUnitsGroup">
            <div class="form-row">
                <div class="form-group">
                    <label>عنوان شرکت / سازمان <span class="hint">(به عنوان OU در CSR)</span></label>
                    <input
                        type="text"
                        name="company_title"
                        id="companyTitle"
                        maxlength="64"
                        placeholder="نام شرکت یا سازمان"
                    >
                </div>

                <div class="form-group">
                    <label>عنوان واحد صنفی</label>
                    <input
                        type="text"
                        name="business_title"
                        maxlength="64"
                        placeholder="نام واحد صنفی"
                    >
                </div>
            </div>
        </div>

        <div class="section-title">اطلاعات تماس و سازمان</div>

        <div class="form-group">
            <label>پست الکترونیکی <span class="badge">اجباری</span></label>
            <input
                type="email"
                name="email"
                id="email"
                required
                placeholder="example@company.ir"
                autocomplete="off"
                dir="ltr"
            >
            <div class="field-error" id="emailError">فرمت ایمیل صحیح نیست</div>
        </div>

        <div class="form-group">
            <label>نقش / سمت <span class="badge">اجباری</span></label>
            <input
                type="text"
                name="role"
                required
                placeholder="مدیر سیستم، حسابدار، ..."
            >
        </div>

        <div class="notice">
            اطلاعات وارد شده صرفاً برای تولید کلید و CSR استفاده می‌شود و در هیچ جایی ذخیره نمی‌گردد.
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit" id="submitBtn">
            تولید کلید و CSR
        </button>

    </form>

</div>

<script>
(function() {

    var provinceData = {};

    var provinceInput   = document.getElementById('provinceInput');
    var provinceValue   = document.getElementById('provinceValue');
    var provinceDD      = document.getElementById('provinceDropdown');
    var provinceError   = document.getElementById('provinceError');

    var cityInput       = document.getElementById('cityInput');
    var cityValue       = document.getElementById('cityValue');
    var cityDD          = document.getElementById('cityDropdown');
    var cityError       = document.getElementById('cityError');

    // ==========================================
    // Province Dropdown
    // ==========================================
    function showProvinceList(filter) {
        var names = Object.keys(provinceData);
        if (filter) names = names.filter(function(n) { return n.indexOf(filter) !== -1; });
        provinceDD.innerHTML = '';
        if (names.length === 0) {
            provinceDD.innerHTML = '<div class="dropdown-item" style="color:#999;cursor:default">نتیجه‌ای یافت نشد</div>';
            return;
        }
        names.forEach(function(name) {
            var d = document.createElement('div');
            d.className = 'dropdown-item' + (name === provinceValue.value ? ' selected' : '');
            d.textContent = name;
            d.addEventListener('mousedown', function(e) {
                e.preventDefault();
                provinceInput.value = name;
                provinceValue.value = name;
                provinceInput.classList.remove('invalid');
                provinceInput.classList.add('valid');
                provinceError.classList.remove('visible');
                provinceDD.classList.remove('active');
                // Enable city
                cityInput.value = '';
                cityInput.placeholder = 'جستجوی شهر...';
                cityInput.removeAttribute('readonly');
                cityInput.focus();
                showCityList('');
                cityDD.classList.add('active');
            });
            provinceDD.appendChild(d);
        });
    }

    provinceInput.addEventListener('focus', function() {
        showProvinceList('');
        provinceDD.classList.add('active');
    });

    provinceInput.addEventListener('input', function() {
        provinceValue.value = '';
        showProvinceList(provinceInput.value.trim());
        provinceDD.classList.add('active');
    });

    provinceInput.addEventListener('blur', function() {
        setTimeout(function() { provinceDD.classList.remove('active'); }, 200);
    });

    // ==========================================
    // City Dropdown
    // ==========================================
    function showCityList(filter) {
        var cities = provinceData[provinceValue.value] || [];
        if (filter) cities = cities.filter(function(c) { return c.indexOf(filter) !== -1; });
        cityDD.innerHTML = '';
        if (cities.length === 0) {
            cityDD.innerHTML = '<div class="dropdown-item" style="color:#999;cursor:default">نتیجه‌ای یافت نشد</div>';
            return;
        }
        cities.forEach(function(city) {
            var d = document.createElement('div');
            d.className = 'dropdown-item' + (city === cityValue.value ? ' selected' : '');
            d.textContent = city;
            d.addEventListener('mousedown', function(e) {
                e.preventDefault();
                cityInput.value = city;
                cityValue.value = city;
                cityInput.classList.remove('invalid');
                cityInput.classList.add('valid');
                cityError.classList.remove('visible');
                cityDD.classList.remove('active');
            });
            cityDD.appendChild(d);
        });
    }

    cityInput.addEventListener('focus', function() {
        if (provinceValue.value) {
            showCityList(cityInput.value.trim());
            cityDD.classList.add('active');
        }
    });

    cityInput.addEventListener('input', function() {
        cityValue.value = '';
        showCityList(cityInput.value.trim());
        cityDD.classList.add('active');
    });

    cityInput.addEventListener('blur', function() {
        setTimeout(function() { cityDD.classList.remove('active'); }, 200);
    });

    // ==========================================
    // Load Data
    // ==========================================
    fetch('data.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            provinceData = data;
        })
        .catch(function() {
            provinceInput.placeholder = 'خطا در بارگذاری استان‌ها';
        });

    // ==========================================
    // Common Name Validation
    // ==========================================
    var commonNameInput = document.getElementById('commonName');
    var commonNameError = document.getElementById('commonNameError');
    var commonNameRegex = /^[A-Za-z0-9._-]+$/;

    var nationalIdInput = document.getElementById('nationalId');
    var nationalIdError = document.getElementById('nationalIdError');
    var nationalCodeInput = document.getElementById('nationalCode');
    var nationalCodeError = document.getElementById('nationalCodeError');

    // ==========================================
    // Real / Legal Person Toggle
    // ==========================================
    var personTypeRadios = document.querySelectorAll('input[name="person_type"]');
    var entitySelector   = document.getElementById('entitySelector');
    var entityTypeGroup  = document.getElementById('entityTypeGroup');
    var nationalIdGroup  = document.getElementById('nationalIdGroup');
    var nationalCodeGroup = document.getElementById('nationalCodeGroup');
    var orgUnitsGroup    = document.getElementById('orgUnitsGroup');
    var nationalCodeLabel = document.getElementById('nationalCodeLabel');

    function applyPersonType() {
        var selected = document.querySelector('input[name="person_type"]:checked');
        var type = selected ? selected.value : 'real';

        // Highlight selected option
        entitySelector.querySelectorAll('.entity-option').forEach(function(opt) {
            opt.classList.toggle('selected', opt.querySelector('input').checked);
        });

        var isReal = (type === 'real');

        entityTypeGroup.classList.toggle('visible', !isReal);
        nationalIdGroup.classList.toggle('visible', !isReal);
        orgUnitsGroup.classList.toggle('visible', !isReal);

        // Required markers / behavior
        if (isReal) {
            nationalCodeLabel.innerHTML = 'کد ملی <span class="hint">(۱۰ رقمی)</span> <span class="badge">اجباری</span>';
            document.querySelector('#entityTypeGroup select').removeAttribute('required');
            document.querySelector('#entityTypeGroup select').value = '';
        } else {
            nationalCodeLabel.innerHTML = 'کد ملی <span class="hint">(۱۰ رقمی - اختیاری)</span>';
            document.querySelector('#entityTypeGroup select').setAttribute('required', 'required');
        }

        // Reset error states
        nationalIdError.classList.remove('visible');
        nationalCodeError.classList.remove('visible');
        nationalIdInput.classList.remove('valid', 'invalid');
        nationalCodeInput.classList.remove('valid', 'invalid');
    }

    personTypeRadios.forEach(function(radio) {
        radio.addEventListener('change', applyPersonType);
    });

    // Initial state (real person by default)
    applyPersonType();

    // ==========================================
    // Common Name Validation
    // ==========================================

    commonNameInput.addEventListener('input', function() {
        var val = this.value;
        if (val === '') {
            this.classList.remove('valid', 'invalid');
            commonNameError.classList.remove('visible');
        } else if (commonNameRegex.test(val) && val.indexOf(' ') === -1) {
            this.classList.remove('invalid');
            this.classList.add('valid');
            commonNameError.classList.remove('visible');
        } else {
            this.classList.remove('valid');
            this.classList.add('invalid');
            commonNameError.classList.add('visible');
        }
    });

    // ==========================================
    // National ID (11 digits)
    // ==========================================
    nationalIdInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value === '') {
            this.classList.remove('valid', 'invalid');
            nationalIdError.classList.remove('visible');
        } else if (/^\d{11}$/.test(this.value)) {
            this.classList.remove('invalid');
            this.classList.add('valid');
            nationalIdError.classList.remove('visible');
        } else {
            this.classList.remove('valid');
            this.classList.add('invalid');
            nationalIdError.classList.toggle('visible', this.value.length === 11);
        }
    });

    // ==========================================
    // National Code (10 digits)
    // ==========================================
    nationalCodeInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value === '') {
            this.classList.remove('valid', 'invalid');
            nationalCodeError.classList.remove('visible');
        } else if (/^\d{10}$/.test(this.value)) {
            this.classList.remove('invalid');
            this.classList.add('valid');
            nationalCodeError.classList.remove('visible');
        } else {
            this.classList.remove('valid');
            this.classList.add('invalid');
            nationalCodeError.classList.toggle('visible', this.value.length === 10);
        }
    });

    // ==========================================
    // Email Validation
    // ==========================================
    var emailInput = document.getElementById('email');
    var emailError = document.getElementById('emailError');
    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    emailInput.addEventListener('input', function() {
        var val = this.value.trim();
        if (val === '') {
            this.classList.remove('valid', 'invalid');
            emailError.classList.remove('visible');
        } else if (emailRegex.test(val)) {
            this.classList.remove('invalid');
            this.classList.add('valid');
            emailError.classList.remove('visible');
        } else {
            this.classList.remove('valid');
            this.classList.add('invalid');
            emailError.classList.add('visible');
        }
    });

    // ==========================================
    // Form Submit Validation
    // ==========================================
    document.getElementById('csrForm').addEventListener('submit', function(e) {
        var valid = true;

        if (!provinceValue.value) {
            provinceInput.classList.add('invalid');
            provinceError.classList.add('visible');
            valid = false;
        }

        if (!cityValue.value) {
            cityInput.classList.add('invalid');
            cityError.classList.add('visible');
            valid = false;
        }

        var cn = commonNameInput.value.trim();
        if (!commonNameRegex.test(cn) || cn.indexOf(' ') !== -1) {
            commonNameInput.classList.add('invalid');
            commonNameError.classList.add('visible');
            valid = false;
        }

        var nid = nationalIdInput.value;
        var ncode = nationalCodeInput.value;
        var personType = document.querySelector('input[name="person_type"]:checked');
        var isReal = (personType && personType.value === 'real');

        if (isReal) {
            // Real person: national code required
            if (!/^\d{10}$/.test(ncode)) {
                nationalCodeInput.classList.add('invalid');
                nationalCodeError.textContent = 'کد ملی باید دقیقاً ۱۰ رقم باشد';
                nationalCodeError.classList.add('visible');
                valid = false;
            }
        } else {
            // Legal person: national ID required, national code optional
            if (!/^\d{11}$/.test(nid)) {
                nationalIdInput.classList.add('invalid');
                nationalIdError.textContent = 'شناسه ملی باید دقیقاً ۱۱ رقم باشد';
                nationalIdError.classList.add('visible');
                valid = false;
            }

            var entityTypeSelect = document.querySelector('#entityTypeGroup select');
            if (entityTypeSelect.value === '') {
                entityTypeSelect.classList.add('invalid');
                valid = false;
            }
        }

        if (!emailRegex.test(emailInput.value.trim())) {
            emailInput.classList.add('invalid');
            emailError.classList.add('visible');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            var firstError = document.querySelector('.field-error.visible');
            if (firstError) {
                firstError.closest('.form-group').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

})();
</script>

</body>
</html>
