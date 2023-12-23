--------------------------------------------------------------------------------
-- GLOBALS
--------------------------------------------------------------------------------

local ouText=nil
local vtHello=nil

local cnt=0

local lReci=nil
local lFilt=nil
local lCodr=nil

local lFound=nil

local nDrums=2
local nSinshi=5
local isHello=true
local isCongr=false

local iCls=0

local itSaved=nil
local isPlacing=0

local psAlchemyMain=nil
local pzCurr=-1

--------------------------------------------------------------------------------
-- EVENT HANDLERS
--------------------------------------------------------------------------------

dbg_out=function(prm,nm)
 if type(prm)=="table" then
  local et=true
  for k,v in pairs(prm) do
   --common.LogInfo("",nm,".",k," - ",tostring(v))
   if (k=="recipes") or (k=="toolImprovers") or (k=="components") then
    if type(v)=="table" then
     common.LogInfo("",nm,".",k," - ",tostring(v)," n=",tostring(table.getn(v)))
    else
     common.LogInfo("",nm,".",k," - ",tostring(v))
    end 
   else
    dbg_out(v,nm.."."..tostring(k))
   end 
   et=false
  end
  if et then 
   common.LogInfo("",nm," - empty table")
  end 
 elseif common.IsWString(prm) then
  common.LogInfo("",nm," (WString) - ",prm)
 else
  common.LogInfo("",nm," (",type(prm),") - ",tostring(prm))
 end
end

MakeReciList=function ()
 if lReci==nil then
  lReci={}
  local ainf=avatar.GetAlchemyInfo()
  if ainf==nil then return end
  for ir,vr in pairs(ainf.recipes) do
   local gr=avatar.GetRecipeInfo(vr)
   local lr={cc=0,wName=gr.name,name=userMods.FromWString(gr.name),score=gr.score,cli={}}
   for ic,vc in pairs(gr.components) do
    local co=avatar.GetComponentInfo(vc)
    local cn=userMods.FromWString(co.name)
    if lr.cli[cn]==nil then lr.cli[cn]=1 else lr.cli[cn]=lr.cli[cn]+1 end
    lr.cc=lr.cc+1
   end
   lReci[ir]=lr
  end
 end 
end

CountReci=function ()
 MakeReciList()
 local tc=0
 local tdc=0
 local drc={}
 itSaved={}
 for dc=1,nDrums do
  local dri=avatar.GetAlchemyDrumInfo(dc-1)
  if dri.itemId~=nil then
   itSaved[dc]=dri.itemId
   tdc=tdc+1
   local d1c={}
   for ic,vc in pairs(dri.components) do
    local gc=avatar.GetComponentInfo(vc)
    local cn=userMods.FromWString(gc.name)
    d1c[cn]=1
   end
   for ic,vc in pairs(d1c) do
    if drc[ic]==nil then drc[ic]=1 else drc[ic]=drc[ic]+1 end
   end
  end
 end
 for ir,vr in pairs(lReci) do
  if vr.cc<=tdc then
   local ifo=true
   for ic,vc in pairs(vr.cli) do
    if (drc[ic]==nil) or (drc[ic]<vc) then
     ifo=false
     break
    end
   end
   if ifo then tc=tc+1 end 
  end
 end
 return tc,tdc
end

MakeCompDrums=function ()
 local drc={}
 lCodr={}
 local tdc=0
 for dru=1,nDrums do
  lCodr[dru]={}
  local d1c={}
  local dri=avatar.GetAlchemyDrumInfo(dru-1)
  if dri.components~=nil then
   tdc=tdc+1
   local cn=table.getn(dri.components)
   if dri.components[cn]~=nil then cn=cn+1 end
   for sh=-6,6 do
    local cp=dri.position+sh
    if cp<0 then cp=cp+cn end
    if cp>=cn then cp=cp-cn end
    if dri.components[cp]~=nil then 
     local gc=avatar.GetComponentInfo(dri.components[cp])
     if gc~=nil then 
      local cn=userMods.FromWString(gc.name)
      lCodr[dru][sh]=cn
      d1c[cn]=1
     end
    end
   end
   for ic,vc in pairs(d1c) do
    if drc[ic]==nil then drc[ic]=1 else drc[ic]=drc[ic]+1 end
   end
  end
 end

 MakeReciList()
 lFilt={}
 local tc=0
 for ir,vr in pairs(lReci) do
  if vr.cc<=tdc then
   local ifo=true
   for ic,vc in pairs(vr.cli) do
    if (drc[ic]==nil) or (drc[ic]<vc) then
     ifo=false
     break
    end
   end
   if ifo then
    table.insert(lFilt,vr)
    tc=tc+1
   end
  end
 end
 return tc
end

TestAddReciWay=function(sm,shis)
 if sm==nil then return end
 local rv=nil
 local ri=nil
 for ir,vr in pairs(lFilt) do
  local ifo=true
  for ic,vc in pairs(vr.cli) do
   if (sm[ic]==nil) or (sm[ic]<vc) then
    ifo=false
    break
   end
  end
  if ifo then
   if (rv==nil) or (rv.score<vr.score) then 
    rv=vr
    ri=ir
   end
  end 
 end
 if rv==nil then return end

 local ifo=false
 for ir,iv in pairs(lFound) do
  if (rv.name==iv.rc.name) then
   ifo=true
   break
  end
 end 
 if ifo==false then
  table.insert(lFound,{rc=rv,sh=MakeTableCopy(shis),cmb=sm})
 end
 
-- table.insert(lFound,{rc=rv,sh=MakeTableCopy(shis),cmb=sm})
-- table.remove(lFilt,ri)
end

MakeTableCopy=function(tbl)
 if tbl==nil then return nil end
 local rv={}
 for ir,vr in pairs(tbl) do 
  rv[ir]=vr
 end
 return rv
end 

SearchShifts=function(opz,shl,shis,sm1,sm2,sm3)
-- if table.getn(lFilt)==0 then return end
 if table.getn(lFilt)==table.getn(lFound) then return end
 if opz>0 then
  if lCodr[opz][0]==nil then
   if (opz>1) or ((opz==1) and (shl==0)) then
    shis[opz]=0
    SearchShifts(opz-1,shl,shis,sm1,sm2,sm3)
   end
   return
  end
  local stt=1
  local sts=shl
  if (opz==1) then
   if shl>nSinshi then return end
   if (shl>0) then stt=2*shl end
  else 
   if shl>nSinshi then sts=nSinshi end
  end
  for shi=-sts,sts,stt do
   local st1=MakeTableCopy(sm1)
   local st2=MakeTableCopy(sm2)
   local st3=MakeTableCopy(sm3)
   local sl=shl-shi
   if shi<0 then sl=shl+shi end
   local ifo=(shi==0)
   local cn
   if st1~=nil then
    cn=lCodr[opz][shi]
    if cn~=nil then
     if st1[cn]==nil then st1[cn]=1 else st1[cn]=st1[cn]+1 end
     ifo=true
    end
   end
   if st2~=nil then
    cn=lCodr[opz][shi-1]
    if cn~=nil then
     if st2[cn]==nil then st2[cn]=1 else st2[cn]=st2[cn]+1 end
     ifo=true
    end
   end
   if st3~=nil then
    cn=lCodr[opz][shi+1]
    if cn~=nil then
     if st3[cn]==nil then st3[cn]=1 else st3[cn]=st3[cn]+1 end
     ifo=true
    end
   end
   if ifo then 
    shis[opz]=shi
    SearchShifts(opz-1,sl,shis,st1,st2,st3)
   end
  end
 else
  TestAddReciWay(sm1,shis)
  TestAddReciWay(sm2,shis)
  TestAddReciWay(sm3,shis)
 end 
end

function OnAlchemyStarted(params)
 local ainf=avatar.GetAlchemyInfo()
 nDrums=ainf.drumsCount
 isPlacing=0
 itSaved={}

common.LogInfo("","OnAlchemyStarted:run")
 local ttt = "LibreAlchemy: Приветствую O_O!"
 cnt=cnt+1
-- local vt=common.CreateValuedText()
-- vt:SetFormat(userMods.ToWString("<html>LibreAlchemy: Приветствую!<br/>Вас!</html>"))
-- ouText:SetValuedText(vt)
 wSetText(ttt,1)
 ouText:Show(true)
 mainForm:Show(true)
 isHello=true
 isCongr=false
-- common.LogInfo("",tostring(cnt)..": "..ttt)
 MakeReciList()
end

function OnAlchemyCanceled(params)
-- common.LogInfo("","OnAlchemyCanceled")
 cnt=cnt+1
 if params.isSuccess==false then
  mainForm:Show(false)
  ouText:Show(false)
  pzCurr=-1
 else 
  isPlacing=9
  local ttt = "LibreAlchemy: Приветствую снова!"
  isHello=true
  if isCongr then
   ttt = "LibreAlchemy: поздравляю!!!"
   isCongr=false
  end
  
  common.LogInfo("","OnAlchemyCanceled:0")
  
  wSetText(ttt,1)
 end 
end

function doCompareRe(a,b)
 if a.rc.score==b.rc.score then
  return a.rc.name>b.rc.name
 else 
  return a.rc.score>b.rc.score
 end
end

function OnAlchemyReactionFinished(params)
-- common.LogInfo("","OnAlchemyReactionFinished")
 cnt=cnt+1
 isCongr=false

 local ainf=avatar.GetAlchemyInfo()
 local dri=avatar.GetAlchemyDrumInfo(0)
 if ainf~=nil then
  nSinshi=dri.maxCorrectionsPerColumn
  local nRota=ainf.correctionCount
  local si=avatar.GetSkillInfo(ainf.id)
  local sm2,sm3=nil,nil
  if avatar.IsAlchemyLineAvailable(-1) then sm2={} end
  if avatar.IsAlchemyLineAvailable(1) then sm3={} end
  MakeCompDrums()
  lFound={}
  for vs=0,nRota do
   SearchShifts(nDrums,vs,{},{},sm2,sm3)
  end 
  if table.getn(lFound)==0 then
   local vt=common.CreateValuedText()
   
   common.LogInfo("","OnAlchemyReactionFinished:{empty}")
   
   wSetText("Тут ничего, кроме бормотухи.",0)
   
   isHello=false
  else
   table.sort(lFound,doCompareRe)
   local fmt=""
   local formLogInfo = "OnAlchemyReactionFinished:"
   local tl=0
   for ir,vr in pairs(lFound) do
    if fmt~="" then 
		fmt=fmt.."<br/>"
		
		formLogInfo = formLogInfo .. "|"
	end
	
    fmt=fmt..string.format("%2d: %2d",vr.rc.score,-vr.sh[1])
	
	formLogInfo = formLogInfo .. string.format("%2d,%2d",vr.rc.score,-vr.sh[1]):gsub( "%s+", "" )
	
    for dc=2,nDrums do
		fmt=fmt..string.format(" |%2d",-vr.sh[dc])
		
		formLogInfo = formLogInfo .. string.format( ",%2d", -vr.sh[dc] ):gsub( "%s+", "" )
    end
	
    fmt=fmt.." - "..vr.rc.name.." "
	
	formLogInfo = formLogInfo .. "," .. vr.rc.name
	
    tl=tl+1
    if tl>5 then break end
   end
   common.LogInfo("",formLogInfo)
   wSetText(fmt,-1)
   isHello=false
  end
 else
  common.LogInfo("","OnAlchemyReactionFinished:0")
  wSetText("LibreAlchemy: ошибка какая-то.",0)
  isHello=false
 end 
end

function OnAlchemyItemPlaced( params )
-- common.LogInfo("","OnAlchemyItemPlaced")
-- dbg_out(params,"ItemPlaced.params")
-- dbg_out(isPlacing,"isPlacing")
 if isPlacing>0 then
  isPlacing=isPlacing-1
  return
 end
 local rc,dc=CountReci()
 local ttt
 cnt=cnt+1
 isCongr=false
 if rc>0 then
 
  common.LogInfo( "", "OnAlchemyItemPlaced:" .. tostring(rc) )
  
  wSetText( "Возможно, есть рецепты: "..tostring(rc).." шт.", 1 )
  
  isHello=false
 elseif (dc==nDrums) or (isHello==false) then
 
  wSetText("Нет тут рецептов",1)
  
  common.LogInfo( "", "OnAlchemyItemPlaced:0" )
  
  isHello=false
 end
end

function OnAlchemyScore(params)
 lReci=nil
 if isHello then
  ttt="LibreAlchemy: поздравляю!"
  wSetText(ttt,1)
 else
  isCongr=true
 end 
end

function OnEventSecondTimer( params )
 isPlacing=0
 if (psAlchemyMain~=nil) and (pzCurr>=0) then
  local pl=psAlchemyMain:GetPlacementPlain()
  local pz
  if pl.sizeY>700 then pz=1 else pz=0 end
  if pzCurr~=pz then
   pzCurr=pz
   if pz>0 then pz=48 end
   local plc=ouText:GetPlacementPlain()
   if plc.posY~=pz then
    plc.posY=pz
    ouText:SetPlacementPlain(plc)
   end
  end
 end 
end

function wSetText(tv,pz)
 if pz<0 then 
--  if psAlchemyMain~=nil then
--   local pl=psAlchemyMain:GetPlacementPlain()
--   dbg_out(pl,'amp2')
--  end
  local vt=common.CreateValuedText()
  vt:SetFormat(userMods.ToWString([[<html><log fontsize="20">]]..tv.."</log></html>"))
  ouText:SetValuedText(vt)
  pz=0
 else
  vtHello:SetVal("ttt",userMods.ToWString(tv))
  ouText:SetValuedText(vtHello)
 end
 if (psAlchemyMain==nil) or (pzCurr==-1) then
  pzCurr=pz
  if pz>0 then pz=48 end
  local plc=ouText:GetPlacementPlain()
  if plc.posY~=pz then
   plc.posY=pz
   ouText:SetPlacementPlain(plc)
  end
 end
end

function onSize(params)
 local pco=widgetsSystem:GetPosConverterParams()
 local plc=mainForm:GetPlacementPlain()
 plc.alignY=WIDGET_ALIGN_HIGH
 plc.highPosY=0
 plc.sizeY=183
 plc.alignX=WIDGET_ALIGN_LOW
 plc.posX=pco.fullVirtualSizeX/2-360
 plc.sizeX=pco.fullVirtualSizeX/2
 mainForm:SetPlacementPlain(plc)
end


--------------------------------------------------------------------------------
-- INITIALIZATION
--------------------------------------------------------------------------------
function Init()
 onSize(nil)
 mainForm:SetPriority(7000)
 mainForm:SetTransparentInput(true)
 mainForm:Show(false)

 ouText=mainForm:GetChildChecked("ouText",false)
 vtHello=common.CreateValuedText()
 vtHello:SetFormat(userMods.ToWString([[<html><log fontsize="20"><r name="ttt"/></log></html>]]))
 vtHello:SetVal("ttt",userMods.ToWString("avaa"))
 ouText:SetValuedText(vtHello)
-- ouText:SetClassVal("cl1",userMods.ToWString("header"));
-- ouText:SetVal("cln",userMods.ToWString("header"));
 ouText:Show(true)
 local plc=ouText:GetPlacementPlain()
 plc.posX=0
 plc.posY=0
 ouText:SetPlacementPlain(plc)
 
 local fsa=stateMainForm:GetChildUnchecked("AlchemyV2", false)
-- dbg_out(fsa,'fsa')
 if fsa~=nil then
  psAlchemyMain=fsa:GetChildUnchecked("MainFrame", false)
 end
-- if psAlchemyMain~=nil then
--  plc=psAlchemyMain:GetPlacementPlain()
--  dbg_out(plc,'amp')
-- end
 
 

 ouText:SetBackgroundColor( { r = 0.1; g = 0.1; b = 0.05; a = 0.9 } )

 common.RegisterEventHandler( OnAlchemyStarted, "EVENT_ALCHEMY_STARTED")
 common.RegisterEventHandler( OnAlchemyCanceled, "EVENT_ALCHEMY_CANCELED")
 common.RegisterEventHandler( OnAlchemyReactionFinished, "EVENT_ALCHEMY_REACTION_FINISHED")
 common.RegisterEventHandler( OnAlchemyItemPlaced, "EVENT_ALCHEMY_ITEM_PLACED")
 common.RegisterEventHandler( OnAlchemyScore, "EVENT_ALCHEMY_RECIPES_CHANGED")
 common.RegisterEventHandler( onSize, "EVENT_POS_CONVERTER_CHANGED")
 common.RegisterEventHandler( OnEventSecondTimer, "EVENT_SECOND_TIMER")
 
end
--------------------------------------------------------------------------------
Init()
--------------------------------------------------------------------------------
