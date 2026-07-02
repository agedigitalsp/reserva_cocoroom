<style>
        
    #floating-button-back {
        position: fixed;
        left: 32px;
        bottom: 32px;
        z-index: 1000;

        transition: bottom 0.5s;
    }

    @media (max-width: 768px){
        #floating-button-back {
            left: 12px;
            bottom: 12px;
        }
    }

    #floating-button-back .button {
        box-shadow: 0px 5px 10px #0002;
        background-color: #333;
        color: #fff;
        border-radius: 43px;
        width: 130px;
        height: 50px;
        display: flex;
        gap: 6px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.3s;
    }

    #floating-button-back .button:hover {
        transform: scale(1.1); 
    }


    #floating-button-back.hidden {
        display:none;
    }

</style>

<div id="floating-button-back" class="hidden">
    <div class="button"><i class="material-icons">keyboard_arrow_left</i> Volver</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnWrapper = document.getElementById('floating-button-back');
    if (!btnWrapper) return;

    btnWrapper.classList.add('hidden');

    var ref = document.referrer || '';

    var vieneDeCocoroomMain = /^https?:\/\/(www\.)?cocoroom\.es(\/|$)/i.test(ref);

    if (vieneDeCocoroomMain) {
        btnWrapper.classList.remove('hidden');
    } else {
        btnWrapper.classList.add('hidden');
    }

    // Acción del botón
    btnWrapper.addEventListener('click', function(e) {
        e.preventDefault();
        window.history.back();
    });
});
</script>