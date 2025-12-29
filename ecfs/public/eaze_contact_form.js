/**
 * EazeWebIT Contact Form Embeddable Script
 */
(function() {
    const API_URL = '/ecfs/public/api/submit.php';

    function init() {
        const forms = document.querySelectorAll('form[eaze-contact-form="true"]');
        forms.forEach(form => {
            form.addEventListener('submit', handleSubmit);
        });
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('[type="submit"]');
        
        if (submitBtn) submitBtn.disabled = true;

        try {
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
            } else {
                console.error(result.message);
            }
        } catch (error) {
            console.error('Submission failed:', error);
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