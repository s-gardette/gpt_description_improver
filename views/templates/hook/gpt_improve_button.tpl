<a class="btn btn-primary" id="gpt-improve-button" href="javascript:void(0);">
    <i class="icon icon-edit"></i> {l s='Improve with ChatGPT' mod='gpt_description_improver'}
</a>
<script>
    document.getElementById('gpt-improve-button').addEventListener('click', function() {
        var originalText = document.getElementById('description_short').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{$gpt_ajax_url}', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('description_short').value = response.improvedText;
                } else {
                    alert('Error: Unable to improve the description.');
                }
            } else {
                alert('Error: Unable to improve the description.');
            }
        };
        xhr.send('description=' + encodeURIComponent(originalText));
    });
</script>
