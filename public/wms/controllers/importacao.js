function startProgress()
{
    var iFrame = document.createElement('iframe');
    document.getElementsByTagName('body')[0].appendChild(iFrame);
    iFrame.src = 'JsPush.php?progress';
}

function Zend_ProgressBar_Update(data)
{
    document.getElementById('pg-percent').style.width = data.percent + '%';
    document.getElementById('pg-text-1').innerHTML = data.text;
    document.getElementById('pg-text-2').innerHTML = data.text;
}

function Zend_ProgressBar_Finish()
{
    document.getElementById('pg-percent').style.width = '100%';
    document.getElementById('pg-text-1').innerHTML = 'Demo done';
    document.getElementById('pg-text-2').innerHTML = 'Demo done';
}

