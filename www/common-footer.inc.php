<footer>
    <p>Version : <?php echo $VERSION; ?></p>
    <?php checkUpdate($BASE_DIR, $VERSION); // on appelle la function checkUpdate en lui transmettant le BASE_DIR et le numéro de version actuelle ?> 
    <a href="https://github.com/lbr38/repomanager" id="github"><img src="images/GitHub-Mark-Light-64px.png" /></a>
</footer>