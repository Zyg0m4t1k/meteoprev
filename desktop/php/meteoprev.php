<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('meteoprev');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
        	<div class="cursor eqLogicAction logoSecondary" data-action="add"  >
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
              <i class="fas fa-wrench"></i>
            <br>
            <span >{{Configuration}}</span>
            </div>            
		</div>	
		<legend>{{Mes Equipements}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
		<?php
			foreach ($eqLogics as $eqLogic) {				
				$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" >';
				echo '<img src="' . $plugin->getPathImgIcon() . '" />';
				echo "<br>";
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			 }
		?>
		</div>
	</div>

    <div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default eqLogicAction btn-sm roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
  		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    		<div role="tabpanel" class="tab-pane active" id="eqlogictab">
      		<br/>
			<form class="form-horizontal">
				<fieldset>
					<div class="form-group">
						<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
						<div class="col-sm-3">
							<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
							<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement meteoprev}}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Objet parent}}</label>
						<div class="col-sm-3">
							<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
								<option value="">{{Aucun}}</option>
								<?php
								foreach (jeeObject::all() as $object) {
									echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
								}
								?>
						   </select>
					   </div>
				   </div>
					<div class="form-group">
						<label class="col-sm-3 control-label">{{Catégorie}}</label>
						<div class="col-sm-9">
						 <?php
							foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
							echo '<label class="checkbox-inline">';
							echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
							echo '</label>';
							}
						  ?>
					   </div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"></label>
						<div class="col-sm-9">
							<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
							<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">{{Nom de la station}}</label>
						<div class="col-sm-3">
							<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="station" placeholder="{{Nom}}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label" >{{Widget personnalisé}}</label>
						<div class="col-md-1" >
							</span><input type="checkbox" class="eqLogicAttr checkbox-inline" data-l1key="configuration"  data-l2key="widgetCustom"  />
						</div>
						<label class="control-label" >{{Valider si vous souhaitez avoir un widget personnalisé}}</label>
					</div>    

				   <div id="custom" style="display:none;">
						<div class="form-group col-sm-12">
						<label class="col-sm-3 control-label"></label>
						<legend>{{Choisir les commandes pour qu'elles apparaissent sur le widget}}</legend>
						</div>
						<div class="form-group temperature">
							<label class="col-sm-3 control-label">Température Extérieure</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="temperature" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="temperature"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>
						 </div> 

						<div class="form-group humidite">
							<label class="col-sm-3 control-label">Humidité Extérieure</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="humidite" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="humidite"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>        
						</div> 
						<div class="form-group pression">
							<label class="col-sm-3 control-label">Pression</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="pression" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="pression"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>        
						 </div>  
						<div class="form-group vent">
							<label class="col-sm-3 control-label">Vent</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="vent" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="vent"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>        
						 </div> 
						<div class="form-group pluieInst">
							<label class="col-sm-3 control-label">Pluie Instantanée</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="pluieInst" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="pluieInst"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>        
						 </div> 
						<div class="form-group pluieTot">
							<label class="col-sm-3 control-label">Pluie Totale</label>
							<div class="col-sm-3 ">
								<div class="input-group">
								<input class="eqLogicAttr form-control input-sm cmdAction" data-l1key="configuration"  data-l2key="pluieTot" />
								<span class="input-group-btn">
								<a class="btn  btn-sm listCmdInfo" data-input="pluieTot"><i class="fas fa-list-alt"></i></a>
								</span>
								</div>
							</div>
							<div class="col-sm-5">
								<span>Si vide la valeur sera celle par défaut</span>
							</div>        
						 </div> 

					</div>

			</fieldset>
			</form>
		</div>
		<div role="tabpanel" class="tab-pane" id="commandtab">
			<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
			<table id="table_cmd" class="table table-bordered table-condensed">
				<thead>
					<tr>
						<th>{{Nom}}</th><th>{{Type}}</th><th>{{Action}}</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>

<?php include_file('desktop', 'meteoprev', 'js', 'meteoprev');?>
<?php include_file('core', 'plugin.template', 'js');?>
