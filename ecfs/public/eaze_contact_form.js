/**
 * EazeWebIT Contact Form Embeddable Script
 */
(function() {
    const API_URL = '/ecfs/public/api/submit.php';
    const CSRF_URL = '/ecfs/public/api/csrf.php';

    function init() {
        const forms = document.querySelectorAll('form[eaze-contact-form="true"]');
        forms.forEach(form => {
            form.addEventListener('submit', handleSubmit);
            
            // Ensure file inputs with 'multiple' attribute have names ending with []
            form.querySelectorAll('input[type="file"][multiple]').forEach(input => {
                if (input.name && !input.name.endsWith('[]')) {
                    input.name += '[]';
                }
            });
        });
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            // Attempt to fetch CSRF token if not already in the form
            if (!form.querySelector('input[name="csrf_token"]')) {
                try {
                    const csrfResp = await fetch(CSRF_URL);
                    if (csrfResp.ok) {
                        const csrfData = await csrfResp.json();
                        if (csrfData.csrf_token) {
                            let csrfInput = form.querySelector('input[name="csrf_token"]');
                            if (!csrfInput) {
                                csrfInput = document.createElement('input');
                                csrfInput.type = 'hidden';
                                csrfInput.name = 'csrf_token';
                                csrfInput.setAttribute('data-injected', 'true');
                                form.appendChild(csrfInput);
                            }
                            csrfInput.value = csrfData.csrf_token;
                        }
                    }
                } catch (csrfError) {
                    // Ignore CSRF fetch errors, as cross-origin might not need it or will fail anyway
                    console.debug('CSRF token fetch skipped or failed:', csrfError);
                }
            }

            // Using FormData(form) will now correctly send multiple files because we ensured names end with []
            const formData = new FormData(form);
            
            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Server returned non-JSON response:', text);
                throw new Error('The server returned an unexpected response. This might be due to a large file upload or server error.');
            }
            
            // Dispatch custom event
            const event = new CustomEvent('eazeContactFormSubmit', {
                detail: {
                    formId: form.getAttribute('eaze-contact-form-id'),
                    status: result.status,
                    message: result.message,
                    form: form
                }
            });
            window.dispatchEvent(event);

            if (result.status === 'success') {
                form.reset();
                // Remove the injected CSRF token so it can be re-fetched if needed for next submission
                const injectedCsrf = form.querySelector('input[name="csrf_token"][data-injected="true"]');
                if (injectedCsrf) {
                    injectedCsrf.remove();
                }
            } else {
                //alert(result.message);
                console.error(result.message);
            }
        } catch (error) {
            console.error('Submission failed:', error);
            //alert('An error occurred during submission. Please try again.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
