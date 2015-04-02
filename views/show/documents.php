








<!-- BEGIN LECTUREDETAILS -->
<section class=contentbox><header><h1>Dateien (zum Herunterladen anklicken)</h1></header>


<!-- BEGIN DOCUMENTS -->	
   


<? foreach ($documents as $document){?>        
<article>
       <a title="Download" href="http://192.168.56.101/el4/vhs-3.1/public/sendfile.php?force_download=1&type=0&file_id=<?= $document['DOCUMENT_ID'] ?>&file_name=<?= $document['DOCUMENT_FILENAME'] ?>"><b>   <?= $document['DOCUMENT_TITLE'] ?> </b> <br>   <?= $document['DOCUMENT_DESCRIPTION'] ?> </a>

</article>
<? } ?>	

 
    <!-- END DOCUMENTS -->
</section>

<br/>




<!-- END LECTUREDETAILS -->