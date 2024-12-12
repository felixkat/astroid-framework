class FormBuilder {
    constructor(el) {
        this.el = el;
        if (this.el.dataset.captcha === 'recaptcha' || this.el.dataset.captcha === 'recaptcha_invisible') {
            this.initReCaptcha();
        }
        this.el.querySelector('.as-form-builer-submit').addEventListener('click', this.onSubmit.bind(this));
    }

    initReCaptcha() {
        let color = 'light';
        if (typeof ASTROID_COLOR_MODE !== 'undefined') {
            color = ASTROID_COLOR_MODE;
        }
        let config = {
            'sitekey': this.el.dataset.sitekey,
            'tabindex': this.el.dataset.tabindex,
            'theme': color
        }
        if (this.el.dataset.captcha === 'recaptcha_invisible') {
            config['badge'] = this.el.dataset.badge;
            config['size'] = 'invisible';
            config['callback'] = this.onCallAjax.bind(this);
        }
        grecaptcha.ready(() => {
            grecaptcha.render(this.el.querySelector('.google-recaptcha'), config);
        });
    }

    onSubmit() {
        if (!this.el.checkValidity()) {
            this.el.classList.add('was-validated');
            return;
        }
        if (this.el.dataset.captcha === 'recaptcha_invisible') {
            grecaptcha.execute();
        } else {
            this.onCallAjax();
        }
    }

    onCallAjax(token) {
        var request = {},
            $this   = jQuery(this.el),
            data    = $this.serializeArray();
        let id = Date.now() * 1000 + Math.random() * 1000;
        id = id.toString(16).replace(/\./g, "").padEnd(14, "0")+Math.trunc(Math.random() * 100000000);
        for (let i = 0; i < data.length; i++) {
            request[data[i]['name']] = data[i]['value'];
        }
        request[$this.find('.token').attr('name')] = 1;
        jQuery.ajax({
            type   : 'POST',
            url    : $this.attr('action')+'&t='+id,
            data   : request,
            beforeSend: function(){
                $this.find('.as-formbuilder-status').empty();
                $this.find('.as-form-builer-submit').attr('disabled', 'disabled');
            },
            success: function (response) {
                if (response.status === 'success') {
                    $this.find('.as-formbuilder-status').append('<div class="alert alert-success" role="alert">'+response.message+'</div>');
                    $this.trigger("reset");
                    $this.find('.google-recaptcha').each(function(){
                        grecaptcha.reset(this);
                    })
                } else {
                    $this.find('.as-formbuilder-status').append('<div class="alert alert-danger" role="alert">'+response.message+'</div>');
                }
                $this.find('.as-form-builer-submit').removeAttr('disabled');
                $this.removeClass('was-validated');
            }
        });
    }
}
jQuery(function($) {
    $('.as-form-builder').each(function() {
        new FormBuilder(this);
    });
});