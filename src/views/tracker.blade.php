<script type="text/javascript">
    var sc_project=<?php echo $pid ?>;
    var sc_invisible=<?php echo ( $isVisible ? '0' : '1' ) ?>;
    var sc_security="<?php echo $security ?>";
    var sc_https=<?php echo ( $isHttps ? '0' : '1' ) ?>;
    var scJsHost = (("https:" == document.location.protocol) ? "https://secure." : "http://www.");
    document.write("<sc"+"ript type=\'text/javascript\' src=\'" + scJsHost + "statcounter.com/counter/counter.js\'></"+"script>");
</script>
<noscript>
    <div class="statcounter">
        <a title="hits counter" href="http://statcounter.com/" target="_blank">
            <img class="statcounter" src="http://c.statcounter.com/<?php echo $pid ?>/0/<?php echo $security ?>/<?php echo ( $isVisible ? '0' : '1' ) ?>/" alt="hits counter">
        </a>
    </div>
</noscript>