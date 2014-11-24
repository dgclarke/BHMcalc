###################################################
#  ____  _    _ __  __           _      
# |  _ \| |  | |  \/  |         | |     
# | |_) | |__| | \  / | ___ __ _| | ___ 
# |  _ <|  __  | |\/| |/ __/ _` | |/ __|
# | |_) | |  | | |  | | (_| (_| | | (__ 
# |____/|_|  |_|_|  |_|\___\__,_|_|\___|
# v2.0
###################################################
# 2014 [)] Jorge I. Zuluaga, Viva la BHM!
###################################################
# Stellar Evolution 
###################################################
from BHM import *
from BHM.BHMplot import *
from BHM.BHMstars import *

###################################################
#CLI ARGUMENTS
###################################################
Usage=\
"""
Usage:
   python %s <sysdir> <module>.conf <qoverride>

   <sysdir>: Directory where the system configuration files lie

   <module>.conf (file): Configuration file for the module.

   <qoverride> (int 0/1): Override any existent object with the same hash.
"""%argv[0]

sys_dir,star_conf,qover=\
    readArgs(argv,
             ["str","str","int"],
             ["sys/template","star1.conf","0"],
             Usage=Usage)

###################################################
#LOAD STAR PROPERTIES
###################################################
PRINTOUT("Loading object from '%s'"%(star_conf))
star,star_str,star_hash,star_dir=makeObject("star",
                                            sys_dir+"/"+star_conf,
                                            qover=qover)
PRINTOUT("Object directory '%s' created"%star_dir)
star_webdir=WEB_DIR+star_dir

#METALLICITY AND ISOCHRONE GRID
if star.Z==0:
     star.Z,dZ=ZfromFHe(star.FeH)
     PRINTOUT("Calculated metallicity:Z=%.4f"%(star.Z))
else:
     star.FeH=FeHfromZ(star.Z)
     PRINTOUT("Calculated metallicity:[Fe/H]=%.4f"%(star.FeH))

###################################################
#LOAD ISOCHRONES
###################################################
zsvec=chooseZsvec(star.Z)
PRINTOUT("Loading isochrone set: %s"%(zsvec))
try:
    zsvec=chooseZsvec(star.Z)
    exec("num=loadIsochroneSet(verbose=False,Zs=%s)"%zsvec)
except:
    PRINTERR("Error loading isochrones.")
    errorCode("FILE_ERROR")

###################################################
#CALCULATE EVOLUTIONARY TRACK
###################################################
#DETERMINING APPROXIMATELY THE MAXIMUM AGE
PRINTOUT("Estimating maximum age...")
tau_max=TAU_MAX
ts=np.linspace(TAU_MIN,TAU_MAX,NTIMES)
for t in ts:
    data=StellarGTRL(star.Z,star.M,t)
    if data[1]<0:
        tau_max=t
        break

#SAMPLING TIMES
exp_ts1=np.linspace(np.log10(TAU_MIN),np.log10(tau_max/2),NTIMES/2)
exp_ts2=-np.linspace(-np.log10(min(TAU_MAX,1.5*tau_max)),-np.log10(tau_max/2),NTIMES/2)
ts=np.unique(np.concatenate((10**exp_ts1,(10**exp_ts2)[::-1])))

#EVOLUTIONARY MATRIX
PRINTOUT("Calculating Evolutionary Matrix...")
evodata=np.array([np.array([t]+list(StellarGTRL(star.Z,star.M,t))) for t in ts])
maxdata=evodata[:,1]>0
evodata=evodata[maxdata]
evodata_str=array2str(evodata)
star.evotrack=evodata

#MAXIMUM ALLOWABLE TIME
tau_max=evodata[-1,0]
PRINTOUT("Maximum age = %.3f"%tau_max)

#DETECTING THE END OF HYDROGEN BURNING
ts=evodata[:,0]
Rs=evodata[:,3]
if star.taums==0:
    tau_ms=disconSignal(ts,Rs,
                        tausys=tau_max/2,
                        iper=3,dimax=10)
else:tau_ms=star.taums
star.taums=tau_ms

###################################################
#RADIUS AND MOMENT OF INERTIA EVOLUTION
###################################################
#GIRATION RADIUS
Nfine=500
star.MoI=np.sqrt(stellarMoI(star.M))
tsmoi=np.logspace(np.log10(TAU_MIN),np.log10(tau_ms),Nfine)

#========================================
#RADIUS EVOLUTION
#========================================
PRINTOUT("Calculating radius evolution...")
star.RMoI=stack(1)
for t in tsmoi:
     logg=StellarProperty('logGravitation',star.Z,star.M,t)
     g=10**logg/100
     R=StellarRadius(star.M,g)
     star.RMoI+=[R]
star.RMoI=toStack(tsmoi)|star.RMoI
Rfunc=interp1d(star.RMoI[:,0],star.RMoI[:,1],kind='slinear')

dRdt=[0]
for i in range(1,Nfine-1):
     dt=(tsmoi[i+1]-tsmoi[i-1])/4
     dRdt+=[(Rfunc(tsmoi[i]+dt)-Rfunc(tsmoi[i]-dt))/(2*dt)]
dRdt[0]=dRdt[1]
dRdt+=[dRdt[-1]]
star.RMoI=toStack(star.RMoI)|toStack(dRdt)

#========================================
#MOMENT OF INERTIA EVOLUTION
#========================================
Ievo=stack(2)
for i in range(Nfine):
     R=star.RMoI[i,1]
     dRdt=star.RMoI[i,2]
     facI=star.MoI*star.M
     I=facI*R**2
     dIdt=2**facI*R*dRdt
     Ievo+=[I,dIdt]
star.RMoI=toStack(star.RMoI)|Ievo

###################################################
#ROTATION EVOLUTION
###################################################
evoInterpFunctions(star)

wini=2*PI/(star.Pini*DAY)
rotpars=dict(\
     star=star,
     starf=None,binary=None,
     taudisk=star.taudisk,
     Kw=star.Kw,
     wsat=star.wsat*OMEGASUN
     )
star.rotevol=odeint(rotationalAcceleration,wini,tsmoi*GYR,args=(rotpars,))
star.rotevol=toStack(tsmoi)|toStack(star.rotevol)

###################################################
#CALCULATE DERIVATIVE INSTANTANEOUS PROPERTIES
###################################################
#BASIC PROPERTIES
g,Teff,R,L=StellarGTRL(star.Z,star.M,star.tau)

#HABITABLE ZONE LIMITS
PRINTOUT("Calculating HZ...")
lins=[]
for incrit in IN_CRITS:
    lin,lsun,lout=HZ(L,Teff,lin=incrit)
    lins+=[lin]
louts=[]
for outcrit in OUT_CRITS:
    lin,lsun,lout=HZ(L,Teff,lout=outcrit)
    louts+=[lout]

#DISSIPATION TIME
tdiss=dissipationTime(star.M,R,L)

title=r"$M_{\\rm star}/M_{\odot}$=%.2f, $Z$=%.4f, $[Fe/H]$=%.2f, $\\tau$=%.2f Gyr"%(star.M,star.Z,star.FeH,star.tau)

###################################################
#FIT ROTATIONAL PERIOD FOR THIS MASS
###################################################
ts=evodata[:,0]
ms=ts<0.9*tau_ms
Rs=evodata[:,3]
prots=stack(1)

for i in xrange(len(ts)):
    t=ts[i]
    Rstar=Rs[i]
    prot=Prot(t,Ms=star.M,Rs=Rstar)/DAY
    prots+=[prot]
prots=prots.array

def chiSquare(x):
    #WEIGHTED BY PERIOD TO ENHANCE EARLY FIT
    residuals=(theoProt(ts[ms],x)-prots[ms])/theoProt(ts[ms],x)
    chisquare=(residuals**2).sum()
    return chisquare

prot_fit=minimize(chiSquare,[1,1.0,1.0]).x

#==============================
#ROTATION EVOLUTION
#==============================
star.protevol=toStack(prots)
star.protevol=toStack(theoProt(ts,prot_fit))|star.protevol
star.protevol=toStack(ts)|toStack(star.protevol)

###################################################
#STORE STELLAR DATA
###################################################
PRINTOUT("Storing stellar data...")
f=open(star_dir+"star.data","w")
f.write("""\
from numpy import array
#MAXIMUM AGE
taumax=%.17e #Gyr
taums=%.17e #Gyr

#INSTANTANEOUS PROPERTIES
title="%s"

#INSTANTANEOUS PROPERTIES
g=%.17e #m/s^2
T=%.17e #L
R=%.17e #Rsun
L=%.17e #Lsun
MoI=%.17e #Gyration radius (I/M R^2)
tdiss=%.17e #s

#ROTATION FIT
protfit=%s

#OTHER PROPERTIES
lins=%s #AU
lsun=%.17e #AU
louts=%s #AU

#EVOLUTIONARY TRACK
evotrack=%s

#ROTATIONAL EVOLUTION
protevol=%s

#ROTATIONAL EVOLUTION
RMoI=%s

#ROTATIONAL EVOLUTION
rotevol=%s
"""%(tau_max,tau_ms,title,
     g,Teff,R,L,star.MoI,tdiss,
     array2str(prot_fit),
     array2str(lins),lsun,array2str(louts),
     evodata_str,
     array2str(star.protevol),
     array2str(star.RMoI),
     array2str(star.rotevol),
     ))
f.close()

###################################################
#GENERATE PLOTS
###################################################
PRINTOUT("Creating plots...")

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#STELLAR PROPERTIES
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"stellar-props",\
"""
from BHM.BHMstars import *
star=\
loadConf("%s"+"star.conf")+\
loadConf("%s"+"star.data")

fig=plt.figure(figsize=(8,6))
ax=fig.add_axes([0.1,0.1,0.8,0.8])
evodata=%s
ts=evodata[:,0]
ts=ts[ts<star.taums]
logrho_func,Teff_func,logR_func,logL_func=evoFunctions(evodata)

ax.plot(ts,10**logrho_func(np.log10(ts))/GRAVSUN,label=r"$g_{\\rm surf}$")
ax.plot(ts,Teff_func(np.log10(ts))/TSUN,label=r"$T_{\\rm eff}$")
ax.plot(ts,10**logR_func(np.log10(ts)),label=r"$R$")
ax.plot(ts,10**logL_func(np.log10(ts)),label=r"$L$")
ax.axvline(star.taums,color='k',linestyle='--',label='Turn Over')
ax.axvline(star.tau,color='k',linestyle='-',label='Stellar Age')

ax.set_xscale('log')
ax.set_yscale('log')

logTickLabels(ax,-2,1,(3,),axis='x',frm='%%.2f')
ax.set_title(star.title,position=(0.5,1.02))
ax.set_xlabel(r"$\\tau$ (Gyr)")
ax.set_ylabel(r"Property in Solar Units")

ymin,ymax=ax.get_ylim()
ax.set_xlim((0,star.taumax))
ax.set_ylim((0.1,10.0))
ax.set_ylim((ymin,ymax))

ax.legend(loc='best',prop=dict(size=12))
"""%(star_dir,star_dir,evodata_str))

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#EVOLUTIONARY TRACK
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"evol-track",\
"""
from BHM.BHMstars import *

fig=plt.figure(figsize=(8,6))
ax=fig.add_axes([0.1,0.1,0.8,0.8])

star=\
loadConf("%s"+"star.conf")+\
loadConf("%s"+"star.data")

evodata=%s
ts=evodata[:,0]
logrho_func,Teff_func,logR_func,logL_func=evoFunctions(evodata)

bbox=dict(fc='w',ec='none')

#LINE
logts=np.log10(ts)
Teffs=Teff_func(logts)
Leffs=10**logL_func(logts)
ax.plot(Teffs,Leffs,"k-")
ax.plot(Teffs[0:1],Leffs[0:1],"ko",markersize=5)
ax.text(Teffs[0],Leffs[0],r"$t_{\\rm ini}$=10 Myr",transform=offSet(5,5),bbox=bbox)
ax.plot([Teffs[-1]],[Leffs[-1]],"ko",markersize=5)
ax.text(Teffs[-1],Leffs[-1],r"$t_{\\rm end}=$%.1f Gyr",horizontalalignment='right',transform=offSet(-5,5),bbox=bbox)

#MARKS
dt=round(%.17e/20,1)
logts=np.log10(np.arange(TAU_MIN,%.17e,dt))
Teffs=Teff_func(logts)
Leffs=10**logL_func(logts)
ax.plot(Teffs,Leffs,"ko",label='Steps of %%.1f Gyr'%%dt,markersize=3)
ax.text(TSUN,1.0,r"$\odot$",fontsize=14)

ax.set_yscale('log')
ax.set_title(star.title,position=(0.5,1.02))
ax.set_xlabel(r"$T_{\\rm eff}$ (K)")
ax.set_ylabel(r"$L/L_{\\rm Sun}$")

Tmin,Tmax=ax.get_xlim()
ax.set_xlim((1E4,1E3))

ax.legend(loc='lower right')
"""%(star_dir,star_dir,
     evodata_str,tau_max,tau_max,tau_max))

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#RADIUS EVOLUTION
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"evol-radius",\
"""
from BHM.BHMstars import *

fig=plt.figure(figsize=(8,6))
ax=fig.add_axes([0.1,0.1,0.8,0.8])

star=\
loadConf("%s"+"star.conf")+\
loadConf("%s"+"star.data")

evodata=%s
ts=evodata[:,0]
logrho_func,Teff_func,logR_func,logL_func=evoFunctions(evodata)

bbox=dict(fc='w',ec='none')

#LINES
logts=np.log10(ts)
Teffs=Teff_func(logts)
Rs=10**logR_func(logts)
ax.plot(Teffs,Rs,"k-")
ax.plot(Teffs[0:1],Rs[0:1],"ko",markersize=5)
ax.text(Teffs[0],Rs[0],r"$t_{\\rm ini}$=10 Myr",transform=offSet(5,5),bbox=bbox)
ax.plot([Teffs[-1]],[Rs[-1]],"ko",markersize=5)
ax.text(Teffs[-1],Rs[-1],r"$t_{\\rm end}=$%.1f Gyr",horizontalalignment='right',transform=offSet(-5,5),bbox=bbox)

#EVOLUTIONARY TRACK
dt=round(%.17e/20,1)
logts=np.log10(np.arange(TAU_MIN,%.17e,dt))
Teffs=Teff_func(logts)
Rs=10**logR_func(logts)
ax.plot(Teffs,Rs,"ko",label='Steps of %%.1f Gyr'%%dt,markersize=3)
ax.text(1.0,1.0,r"$\odot$",fontsize=14)

ax.set_title(star.title,position=(0.5,1.02))
ax.set_xlabel(r"$T_{\\rm eff}$ (K)")
ax.set_ylabel(r"$R/R_{\\rm Sun}$")

Tmin,Tmax=ax.get_xlim()
ax.set_xlim((Tmax,Tmin))

ax.legend(loc='lower right')
"""%(star_dir,star_dir,
     evodata_str,tau_max,tau_max,tau_max))

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#RADIUS EVOLUTION
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"evol-RMoI",\
"""
from BHM.BHMstars import *

fig=plt.figure(figsize=(8,8))
l=0.1;b=0.1;w=0.85;h=0.55;ho=0.01
ax_dI=fig.add_axes([l,b,w,h/2])
b+=h/2+ho
ax_I=fig.add_axes([l,b,w,h])

star=\
loadConf("%s"+"star.conf")+\
loadConf("%s"+"star.data")
evoInterpFunctions(star)

#MAIN PLOT
ts=star.RMoI[:,0]
I=star.RMoI[:,3]*MSUN*RSUN**2
Imin=min(I);Imax=max(I)
dIdt=star.RMoI[:,4]*MSUN*RSUN**2/GYR

ax_dI.plot(ts,np.abs(dIdt)/I,'-')
ax_I.plot(ts,I,'-')

#DECORATIONS
for ax in ax_I,ax_dI:
    ax.set_xscale('log')
    ax.set_yscale('log')

#I-TICKS
ax_I.set_ylim((Imin,Imax))
It=[];Il=[]
for I in np.linspace(Imin,Imax,10):
    It+=[I]
    Il+=["%%.2f"%%np.log10(I)]
ax_I.set_yticks(It)
ax_I.set_yticklabels(Il,fontsize=10)

ax_I.set_xticklabels([])
dIl=ax_dI.get_yticks()
ax_dI.set_yticks(dIl[:-1])

ax_I.set_title(star.title,position=(0.5,1.02))
ax_dI.set_xlabel(r"$\\tau$ (Gyr)")
ax_I.set_ylabel(r"$\log\,I$ (kg m$^2$)")
ax_dI.set_ylabel(r"$-|dI/dt|/I$ (s$^{-1}$)")

ax_I.grid()
ax_dI.grid()

"""%(star_dir,star_dir))

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#RADIUS SCHEMATIC
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"radius-schematic",\
"""
from BHM.BHMstars import *
fig=plt.figure(figsize=(8,8))
ax=fig.add_axes([0.0,0.0,1.0,1.0])

M=%.3f
Z=%.4f
tau=%.3f
R=%.3f
T=%.1f

color=cm.RdYlBu((T-2000)/7000)

star=patches.Circle((0.0,0.0),R,fc=color,ec='none')
sun=patches.Circle((0.0,0.0),1.0,
                   linestyle='dashed',fc='none',zorder=10)
ax.add_patch(star)
ax.add_patch(sun)
ax.text(0.0,1.0,'Sun',fontsize=20,transform=offSet(0,5),horizontalalignment='center')

ax.set_xticks([])
ax.set_yticks([])

ax.set_title(r"$M = %%.3f\,M_{\odot}$, $Z=$%%.4f, $\\tau=%%.3f$ Gyr, $R = %%.3f\,R_{\odot}$, $T_{\\rm eff} = %%.1f$ K"%%(M,Z,tau,R,T),
position=(0.5,0.05),fontsize=16)

rang=max(1.5*R,1.5)
ax.set_xlim((-rang,+rang))
ax.set_ylim((-rang,+rang))
"""%(star.M,star.Z,star.tau,R,Teff),watermarkpos="inner")

#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
#STELLAR ROTATION
#%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
plotFigure(star_dir,"stellar-rotation",\
"""
from BHM.BHMstars import *
star=\
loadConf("%s"+"star.conf")+\
loadConf("%s"+"star.data")

fig=plt.figure(figsize=(8,6))
ax=fig.add_axes([0.1,0.1,0.8,0.8])

ts=star.protevol[:,0]
Psemi=star.protevol[:,1]
Panal=star.protevol[:,2]
ax.plot(ts,Psemi,label="Fit")
ax.plot(ts,Panal,label="Model")
ax.axvline(star.taums,color='k',linestyle='--',label='Turn over')
ax.axvline(star.tau,color='k',label='Stellar Age')

ax.set_title(star.title,position=(0.5,1.02))
ax.set_ylabel("Period (days)")
ax.set_xlabel(r"$\\tau$ (Gyr)")

ax.set_xlim((0,min(12,star.taumax)))
ax.legend(loc='best')
"""%(star_dir,star_dir))

###################################################
#GENERATE FULL REPORT
###################################################
fh=open(star_dir+"star.html","w")
fh.write("""\
<head>
  <link rel="stylesheet" type="text/css" href="%s/BHM.css">
</head>
<h2>Stellar Properties</h2>
<center>
  <a href="%s/radius-schematic.png" target="_blank">
    <img width=60%% src="%s/radius-schematic.png">
  </a>
  <br/>
  <i>Schematic Representation</i>
  (
  <a href="%s/radius-schematic.png.txt" target="_blank">data</a>|
  <a href="%s/BHMreplot.php?dir=%s&plot=radius-schematic.py" target="_blank">replot</a>
  )
</center>
</table>
<h3>Input properties</h3>
<table>
  <tr><td>Mass (M<sub>sun</sub>):</td><td>%.3f</td></tr>
  <tr><td>Z:</td><td>%.4f</td></tr>
  <tr><td>[Fe/H] (dex):</td><td>%.2f</td></tr>
  <tr><td>&tau; (Gyr):</td><td>%.2f</td></tr>
  <tr><td>&tau;<sub>max</sub> (Gyr):</td><td>%.2f</td></tr>
  <tr><td>&tau;<sub>MS</sub> (Gyr):</td><td>%.2f</td></tr>
  <tr><td>Hash:</td><td>%s</td></tr>
</table>
<h3>Instantaneous theoretical properties:</h3>
<table>
  <tr><td>g (m/s<sup>2</sup>):</td><td>%.2f</td></tr>
  <tr><td>T<sub>eff</sub> (K):</td><td>%.2f</td></tr>
  <tr><td>R/R<sub>sun</sub>:</td><td>%.3f</td></tr>
  <tr><td>L/L<sub>sun</sub>:</td><td>%.3f</td></tr>
  <tr><td>MoI=I/MR<sup>2</sup>:</td><td>%.3f</td></tr>
  <tr><td>t<sub>diss</sub> (yr):</td><td>%.3f</td></tr>
</table>
<h3>Circumstellar Habitable Zone:</h3>
<table>
  <tr><td>l<sub>in</sub> (AU):</td><td>(Recent Venus) %.2f, (Runaway Greenhouse) %.2f, (Moist Greenhous) %.2f</td></tr>
  <tr><td>l<sub>out</sub> (AU):</td><td>(Maximum Greenhouse) %.2f, (Early Mars) %.2f</td></tr>
</table>

<h3>Evolution of Stellar Properties:</h3>
<table>
  <tr><td>
      <a href="%s/stellar-props.png" target="_blank">
	<img width=100%% src="%s/stellar-props.png">
      </a>
      <br/>
      <i>Evolution of stellar properties</i>
	(
	<a href="%s/stellar-props.png.txt" target="_blank">data</a>|
	<a href="%s/BHMreplot.php?dir=%s&plot=stellar-props.py" target="_blank">replot</a>
	)
  </td></tr>
  <tr><td>
      <a href="%s/evol-track.png" target="_blank">
	<img width=100%% src="%s/evol-track.png">
      </a>
      <br/>
      <i>Evolutionary Track</i>
	(
	<a href="%s/evol-track.png.txt" target="_blank">data</a>|
	<a href="%s/BHMreplot.php?dir=%s&plot=evol-track.py" target="_blank">replot</a>
	)
  </td></tr>
  <tr><td>
      <a href="%s/evol-radius.png" target="_blank">
	<img width=100%% src="%s/evol-radius.png">
      </a>
      <br/>
      <i>Radius Track</i>
	(
	<a href="%s/evol-radius.png.txt" target="_blank">data</a>|
	<a href="%s/BHMreplot.php?dir=%s&plot=evol-radius.py" target="_blank">replot</a>
	)
  </td></tr>
</table>
<h3>Evolution of Rotational Properties:</h3>
<table>
  <tr><td>
      <a href="%s/evol-RMoI.png" target="_blank">
	<img width=100%% src="%s/evol-RMoI.png">
      </a>
      <br/>
      <i>Moment of Inertia Evolution</i>
	(
	<a href="%s/evol-RMoI.png.txt" target="_blank">data</a>|
	<a href="%s/BHMreplot.php?dir=%s&plot=evol-RMoI.py" target="_blank">replot</a>
	)
  </td></tr>
  <tr><td>
      <a href="%s/stellar-rotation.png" target="_blank">
	<img width=100%% src="%s/stellar-rotation.png">
      </a>
      <br/>
      <i>Stellar Rotation Evolution</i>
	(
	<a href="%s/stellar-rotation.png.txt" target="_blank">data</a>|
	<a href="%s/BHMreplot.php?dir=%s&plot=stellar-rotation.py" target="_blank">replot</a>
	)
  </td></tr>
</table>
"""%(WEB_DIR,star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
star.M,star.Z,star.FeH,star.tau,tau_max,tau_ms,star_hash,
g,Teff,R,L,star.MoI,tdiss,
lins[0],lins[1],lins[2],
louts[0],louts[1],
star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
star_webdir,star_webdir,star_webdir,WEB_DIR,star_webdir,
))
fh.close()

###################################################
#CLOSE OBJECT
###################################################
closeObject(star_dir)
